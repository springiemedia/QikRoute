<?php
/* ========================================================================= */
/* R E Q U E S T                                                             */
/* ========================================================================= */
/*                                                                           */
/* Class that parses the request and stores into class propeties.            */
/*                                                                           */
/* Created by: Callum Springford                                             */
/* Created on: 2017-04-23                                                    */
/*                                                                           */
/* (c) Copyright Callum Springford 2017. All rights reserved.                */
/*                                                                           */
/* ========================================================================= */

namespace Framework;

class Request
{

    public static $route;
    public static $slugs;

    // Globals
    public static $get;
    public static $post;
    public static $files;

    // Server
    public static $user_agent;
    public static $ip;
    public static $protocol;
    public static $method;
    public static $uri;

    /**
     * Init
     *
     * Main method of class. Parses request.
     *
     * @param        NONE
     *
     * @return       NONE
     *
     */
    public static function init()
    {

        self::$route  = $_GET['__route'];
        unset($_GET['__route']);

        // If no route, then default to '/'. This helps routing aswell.
        if(!self::$route) {
            self::$route = '/';
        }

        // Check if request has a traling slash (if not a file). If not then redirect to same
        // URL with a traling slash.
        self::checkForTrailingSlash();

        // Store pointers to globals.
        self::$get        = &$_GET;
        self::$post       = &$_POST;
        self::$files      = &$_FILES;

        // Store useful request data.
        self::$user_agent = &$_SERVER['HTTP_USER_AGENT'];
        self::$ip         = self::get_ip();
        self::$protocol   = &$_SERVER['SERVER_PROTOCOL'];
        self::$method     = &$_SERVER['REQUEST_METHOD'];
        self::$uri        = &$_SERVER['REQUEST_URI'];
        self::$slugs      = array_filter(explode('/', self::$route));

        self::$uri        = $_SERVER['REQUEST_URI'];
        self::$method     = $_SERVER['REQUEST_METHOD'];

    }


    /**
     * Check For Trailing Slash
     *
     * If the current request is not for a file, then check if URI has trailing slash. If no slash is found then
     * a redirect is made to the same url + /.
     *
     * @param         NONE
     *
     * @return        NONE
     *
     */
    public static function checkForTrailingSlash()
    {

        // If does not have file extension.
        if(self::$uri != '' && !self::is_file(self::$uri))
        {

            // If no traling slash and $_GET is not set.
            if(self::$uri != '' && !self::$get && !preg_match('/\/$/', self::$uri))
            {

                $slash_url = ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]  == "on" ? 'https://' : 'http://') . $_SERVER["SERVER_NAME"] . ($_SERVER["SERVER_PORT"] != "80" ? ':' . $_SERVER["SERVER_PORT"] : '') . $_SERVER['REQUEST_URI']);
                header('HTTP/1.0 302 Permanent Redirect');
                header('Location:' . $slash_url . '/');
                exit();

            }

        }

    }

    /**
     * Get IP
     *
     * Returns the real IP address of the requester.
     *
     * @param         NONE
     *
     * @return        STRING
     *
     */
    private static function get_ip()
    {

        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP))
        {

            $ip = $client;

        }
        elseif(filter_var($forward, FILTER_VALIDATE_IP))
        {

            $ip = $forward;

        }
        else
        {

            $ip = $remote;

        }

        return $ip;

    }

    /**
     * Is File
     *
     * @param      STRING      $url     - String to check if it looks like a file.
     *
     * @return     BOOL                 - True for file, false for not.
     *
     */
    public static function is_file($url)
    {

        if(preg_match('/\.[a-zA-Z0-9]{2,}/', $url))
        {

            return true;

        }

        return false;

    }


} // End class.