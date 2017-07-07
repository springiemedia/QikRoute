<?php
/* ========================================================================= */
/* R O U T E                                                                 */
/* ========================================================================= */
/*                                                                           */
/* Manages routing of URI's to controllers.                                  */
/*                                                                           */
/* Created by: Callum Springford                                             */
/* Created on: 2017-04-23                                                    */
/*                                                                           */
/* (c) Copyright Callum Springford 2017. All rights reserved.                */
/*                                                                           */
/* ========================================================================= */

namespace Framework;

class Route
{

    protected static $allowed_methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    public static $route_register;
    protected static $current_route_group_attributes = [];
    public static $route_name_map = [];

    protected static $route_name;
    protected static $last_route_added = false;
    protected static $route_name_register = [];

    /**
     * Register Route
     *
     * Registers a routes details to $this->route_register.
     *
     * @param      STRING               $method
     *
     * @param      STRING               $uri
     *
     * @param      STRING/CLOSURE       $controller_or_closure
     *
     * @param      ARRAY                $options
     *                                     - section_slug_override : pass a valid section name to use its slug instead of
     *                                                               current section.
     *
     * @return     NONE
     *
     */
    protected static function register_route($method, $uri, $controller_or_closure, $action = false, $options = false)
    {

        // Remove leading slash.
        $uri              = preg_replace('/^\/.+/', '', $uri);

        // Set any group attributes.
        if($controller_or_closure instanceof \Closure) {

            // Extract URI parameters from URI.
            preg_match_all('!\{([a-z0-9\-_]+)\}!', $uri, $uri_parameters);

            $route_attributes = [
                'closure'         => $controller_or_closure,
                'parameters'      => array_flip($uri_parameters[1])
            ];

        }
        elseif(is_string($controller_or_closure)) {

            $pi = pathinfo($controller_or_closure);

            $route_attributes = [
                'controller_name' => $pi['filename'],
                'controller_file' => 'app/controller/' . $controller_or_closure . '.php',
                'action'          => $action . 'Action',
                'namespace'       => (isset($options['namespace']) ? $options['namespace'] . '\\' : '\\App\\Controller\\'),
                'name'            => (!self::$route_name ? false : self::$route_name)
            ];

            // Reset route name to prevent bleed.
            self::$route_name = false;

        }

        $route_attributes = array_merge(self::$current_route_group_attributes, $route_attributes);

        self::$route_register[$method][$uri] = $route_attributes;

    }

    public static function register_route_name($name, $method, $uri, $controller_or_closure, $action = false, $options = false)
    {

        // Remove leading slash.
        $uri              = preg_replace('/^\/.+/', '', $uri);

        // Set any group attributes.
        if($controller_or_closure instanceof \Closure) {

            // Extract URI parameters from URI.
            preg_match_all('!\{([a-z0-9\-_]+)\}!', $uri, $uri_parameters);

            $route_attributes = [
                'uri'             => $uri,
                'closure'         => $controller_or_closure,
                'parameters'      => array_flip($uri_parameters[1])
            ];

        }
        elseif(is_string($controller_or_closure)) {

            $pi = pathinfo($controller_or_closure);

            $route_attributes = [
                'uri'             => $uri,
                'controller_name' => $pi['filename'],
                'controller_file' => 'app/controller/' . $controller_or_closure . '.php',
                'action'          => $action . 'Action',
                'namespace'       => (isset($options['namespace']) ? $options['namespace'] . '\\' : '\\App\\Controller\\'),
                'section_config'  => self::$current_section_config
            ];

            // Reset route name to prevent bleed.
            self::$route_name = false;

        }

        $route_attributes = array_merge(self::$current_route_group_attributes, $route_attributes);

        self::$route_name_register[$name] = $route_attributes;

    }


    /**
     * Group
     *
     * Method that can wrap around standard Route::add calls to assign common attributes to one or more routes.
     *
     * @param      ARRAY         $attributes
     *
     * @param      CLOSURE       $route_closure
     *
     * @return     NONE
     *
     */
    public static function group($attributes, $route_closure)
    {

        self::$current_route_group_attributes = $attributes;

        if($route_closure instanceof Closure) {

            call_user_func_array($route_closure,[]);

        }

        // Clear current route group attributes array as group has now been processed.
        self::$current_route_group_attributes = [];

    }

    /**
     * Add
     *
     * Adds a route.
     *
     * Closure    - Route::add('GET', 'account/([a-z]/[0-9]/', function($char, $num)) {} );
     * Controller - Route::add('GET', 'user/([0-9])', 'user', 'index'); // Matches passed to method args
     *
     * @param      STRING      $method        - GET | POST | PUT | PATCH | DELETE
     *
     * @return     NONE
     *
     */
    public static function add($request_method, $uri, $controller_or_closure, $action = 'index', $options = false)
    {

        $request_methods         = explode('|', $request_method);

        $invalid_request_methods = array_diff($request_methods, self::$allowed_methods);

        if(count($invalid_request_methods)) {

            echo 'Invalid request method';

        }
        else {


            foreach($request_methods as $method) {

                self::$last_route_added = [strtoupper($method), $uri, $controller_or_closure, $action, $options];
                self::register_route(strtoupper($method), $uri, $controller_or_closure, $action, $options);

            }

        }

        return new Route();

    }


    /**
     * Name
     *
     * Call after Route::name() to name the route.
     *
     * @param      STRING         $name          - String of input.
     *
     * @return     NONE
     *
     */
    public function name($name)
    {

        if(self::$last_route_added) {

            self::register_route_name($name, self::$last_route_added[0], self::$last_route_added[1], self::$last_route_added[2], self::$last_route_added[3], self::$last_route_added[4]);

        }

    }


    /**
     * URI
     *
     * Returns the absolute URI of a names route.
     *
     * @param      STRING         $name          - String of input.
     *
     * @return     STRING/BOOL                   - URI of route name or false if not found.
     *
     */
    public static function URI($name)
    {

        if(isset(self::$route_name_register[$name])) {

            return self::$route_name_register[$name]['uri'];

        }

        return false;

    }


    /**
     * Parse Macro
     *
     * Parses a string for regex placeholders and replaces them with the corresponding
     * regex pattern.
     *
     * @param      STRING         $input          - String of input.
     *
     * @return     STRING         $input          - String with regex replacements.
     *
     */
    protected static function parseMacro($input) {

        // Pattern macros. Quick place holders to regex.
        $input = str_replace([
            '{:ID?}',
            '{:INT?}',
            '{:SID?}',
            '{:CHAR?}',
            '{:STRING?}',
            '{:ID}',
            '{:INT}',
            '{:SID}',
            '{:CHAR}',
            '{:STRING}',
            ':ID',
            ':INT',
            ':SID',
            ':CHAR',
            ':STRING'
        ],[
            '([0-9]{0,10})/?',
            '([0-9]*)/?',
            '([0-9]{0,4,6,10})/?',
            '([a-z]*)/?',
            '([a-z\-\_0-9]*)/?',
            '([0-9]{10})',
            '([0-9]+)',
            '([0-9]{4,6,10})',
            '([a-z])',
            '([a-z\-\_0-9]+)',
            '[0-9]{10}',
            '[0-9]+',
            '[0-9]{4,6,10}',
            '[a-z]',
            '[a-z\-\_0-9]'

        ], $input);

        return $input;

    }


    /**
     * Parse
     *
     * Parses all routes in route register against the request URI.
     *
     * @param      NONE
     *
     * @return     NONE
     *
     */
    public static function parse() {

        // Ensure there are routes registered for this request method.
        if(count(self::$route_register[Request::$method])) {

            foreach(self::$route_register[Request::$method] as $uri_pattern => $route_datum) {

                $uri_pattern = self::parseMacro($uri_pattern);

                // If route matched uri pattern, then fall through.
                if($uri_pattern == Request::$route || preg_match('!^' . $uri_pattern . '!', Request::$route, $uri_parameters)) {

                    // Remove global match from array.
                    if(isset($uri_parameters)) {

                        array_shift($uri_parameters);

                    }
                    // If / then $uri_parameters will be null so set it as empty.
                    else {

                        $uri_parameters = [];

                    }

                    // If action is a callback.
                    if(isset($route_datum['closure'])) {

                        // Echo out closure result.
                        echo call_user_func_array($route_datum['closure'], $uri_parameters);

                        exit;

                    }
                    // Controller.
                    elseif(is_file($route_datum['controller_file'])) {

                        require($route_datum['controller_file']);

                        // Build controller name, with any namespace attribute.
                        $controller_name  = $route_datum['namespace'] . $route_datum['controller_name'];

                        $controller       = new $controller_name();
                        $controller->args = $uri_parameters;

                        $result = call_user_func_array([
                            $controller, $route_datum['action']
                        ],
                            $uri_parameters
                        );

                        // Output result. Could pass to a response class.
                        echo $result;

                        exit;

                    }

                }

            }

        }

    }

} // End Class.