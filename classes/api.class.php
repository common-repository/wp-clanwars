<?php
/*
    WP-Clanwars
    (c) 2011 Andrej Mihajlov

    This file is part of WP-Clanwars.

    WP-Clanwars is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WP-Clanwars is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WP-Clanwars.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace WP_Clanwars;

final class API {

    // 15s timeout for API requests
    const TIMEOUT = 15;

    // private static $api_url = 'http://localhost:3000/api/v1/';
    private static $api_url = 'https://wp-clanwars.herokuapp.com/api/v1/';

    private static $client_key_option_key = '_wp-clanwars-server-clientkey';

    private static $access_token_usermeta_key = 'wp-clanwars-server-accesstoken';
    private static $user_info_usermeta_key = 'wp-clanwars-server-userinfo';

    static function check_client_key() {
        static $checked = false;

        $client_key = static::get_client_key();

        if(!empty($client_key)) {
            return true;
        }

        if($checked) {
            return false;
        }

        $checked = true;

        $args = array(
            'user-agent' => static::get_user_agent(),
            'body' => array(
                'siteUrl' => site_url()
            )
        );

        $response = wp_remote_post( static::$api_url . 'installation/register', $args );
        $payload = static::get_response_payload( $response );
        if(is_wp_error($payload)) {
            return $payload;
        }

        if(!isset($payload->clientKey)) {
            return new \WP_Error( 'api-error', 0, 'Invalid client key.' );
        }

        update_option( static::$client_key_option_key, $payload->clientKey );

        return true;
    }

    static function is_logged_in() {
        return !empty( static::get_access_token() );
    }

    static function logout() {
        static::set_access_token('');
        static::set_user_info('');
    }

    static function update_account($account_info) {
        $update_response = static::api_patch( static::$api_url . 'user', array(
            'body' => $account_info
        ) );

        if ( is_wp_error($update_response) ) {
            return $update_response;
        }

        $access_token = static::get_access_token();
        $status = static::get_auth_status($access_token);
        if ( is_wp_error($status) ) {
            return $status;
        }

        static::set_user_info($status);

        return true;
    }

    static function get_login_url($service, $callbackUrl) {
        return static::$api_url . 'auth/' . $service . '?returnTo=' . urlencode($callbackUrl);
    }

    static function update_access_token($access_token) {
        $status = static::get_auth_status($access_token);

        if ( is_wp_error($status) ) {
            static::set_access_token('');
            static::set_user_info('');

            return $status;
        } else {
            static::set_access_token($access_token);
            static::set_user_info($status);

            return true;
        }
    }

    private static function get_access_token() {
        global $current_user;
        return get_user_meta( $current_user->ID, static::$access_token_usermeta_key, true );
    }

    private static function set_access_token($access_token) {
        global $current_user;
        update_user_meta( $current_user->ID, static::$access_token_usermeta_key, $access_token );
    }

    private static function set_user_info($userInfo) {
        global $current_user;
        update_user_meta( $current_user->ID, static::$user_info_usermeta_key, $userInfo );
    }

    static function get_user_info() {
        global $current_user;
        return get_user_meta( $current_user->ID, static::$user_info_usermeta_key, true );
    }

    private static function get_auth_status( $access_token ) {
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token
            )
        );

        return static::api_get( static::$api_url . 'auth/status', $args );
    }

    static function download_game($id, $filename) {
        $zip_url = static::$api_url . 'games/download/' . $id;

        $response = wp_remote_get( $zip_url, static::setup_args(array(
            'stream' => true,
            'filename' => $filename
        ) ) );

        if( is_wp_error( $response ) ) {
            return $response;
        }

        if( wp_remote_retrieve_response_code($response) !== 200 ) {
            return new \WP_Error( 'download-error', __('File is not found on server.', WP_CLANWARS_TEXTDOMAIN) );
        }

        return true;
    }

    static function get_game($id) {
        return static::api_get( static::$api_url . 'games/' . $id );
    }

    static function game_vote($id, $rating) {
        return static::api_post( static::$api_url . 'games/' . $id . '/vote',
                array(
                    'body' => array( 'rating' => $rating )
                )
            );
    }

    static function get_popular() {
        return static::api_get( static::$api_url . 'games/popular' );
    }

    static function search($term) {
        return static::api_get( static::$api_url . 'games/search?q=' . urlencode($term) );
    }

    static function get_published_games($user_id = 'me') {
        return static::api_get( static::$api_url . 'user/' . $user_id . '/games' );
    }

    static function publish($zip_file) {
        if(!function_exists('curl_init')) {
            return new \WP_Error( 'api-error', 0, 'Unable to locate cURL extension.' );
        }

        $zip_file = realpath($zip_file);
        $data = array();

        $headers = array(
            'User-Agent: ' . static::get_user_agent(),
            'Content-Type: multipart/form-data',
            'X-Client-Key: ' . static::get_client_key(),
            'Authorization: Bearer ' . static::get_access_token()
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, static::$api_url . 'games');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, static::TIMEOUT);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, static::get_user_agent());

        // use safe cURL uploads when possible
        if( function_exists( 'curl_file_create' ) ) { // php 5.5+
            // disable unsafe uploads (true is default in php 5.6+)
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);

            $data['file'] = curl_file_create($zip_file, 'application/zip', 'payload.zip');
        }
        else { // php 5.2+
            // filter out attempts to upload files from server
            // by prefixing text fields with @
            // Remove @ from the beginning of each value.
            array_walk($data, function (&$val) {
                $val = preg_replace('#^@#i', '', trim( (string)$val ));
            });

            $data['file'] = '@' . $zip_file . ';type=application/zip';
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);

        if($response === false) {
            $err = curl_error($ch);
            $code = curl_errno($ch);
            return new \WP_Error( 'api-error-curl', $code, $err );
        }

        $info = curl_getinfo($ch);
        $http_code = (int) $info['http_code'];
        if( $http_code === 401 ) {
            return new \WP_Error( 'api-error-authorization', __( 'Authorization required.', WP_CLANWARS_TEXTDOMAIN ) );
        }

        return static::get_response_payload($response);
    }

    private static function get_client_key() {
        return (string) get_option( static::$client_key_option_key );
    }

    private static function get_user_agent() {
        global $wp_version;

        return 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) . '; WP-Clanwars/' . WP_CLANWARS_VERSION;
    }

    private static function api_get($url, $args = array()) {
        $ok = static::check_client_key();

        if($ok === true)
        {
            $response = static::remote_get( $url, $args );

            return static::get_response_payload( $response );
        }

        return $ok;
    }

    private static function api_post($url, $args = array()) {
        $ok = static::check_client_key();

        if($ok === true)
        {
            $response = static::remote_post( $url, $args );

            return static::get_response_payload( $response );
        }

        return $ok;
    }

    private static function api_patch($url, $args = array()) {
        $ok = static::check_client_key();

        if($ok === true)
        {
            $response = static::remote_patch( $url, $args );

            return static::get_response_payload( $response );
        }

        return $ok;
    }

    private static function remote_get($url, $args = array()) {
        return wp_remote_get( $url, static::setup_args( $args ) );
    }

    private static function remote_post($url, $args = array()) {
        return wp_remote_post( $url, static::setup_args( $args ) );
    }

    private static function remote_patch($url, $args = array()) {
        return wp_remote_request( $url,
                static::setup_args(
                    array_merge($args, array(
                        'method' => 'PATCH'
                    )
                )
            )
        );
    }

    private static function setup_args($args) {
        $headers = array();

        if(static::is_logged_in()) {
            $headers['Authorization'] = 'Bearer ' . static::get_access_token();
        }

        $headers['X-Client-Key'] = static::get_client_key();

        $base_args = array(
            'user-agent' => static::get_user_agent(),
            'headers' => $headers,
            'timeout' => static::TIMEOUT
        );

        return array_merge($base_args, $args);
    }

    private static function get_response_payload($response) {
        if(is_wp_error($response)) {
            return $response;
        }

        $api_response = json_decode( is_string($response) ? $response : wp_remote_retrieve_body($response) );

        if(!$api_response->success) {
            $response_error = $api_response->error;
            return new \WP_Error( 'api-error-' . $response_error->code, $response_error->message );
        }

        return $api_response->payload;
    }

}