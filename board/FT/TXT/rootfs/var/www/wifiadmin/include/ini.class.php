<?php
/* arnapou
03-Oct-2007 04:51
I didn't find a simple ini class so I wrote that class to read and write ini files.
I hope it could help you.

Read file : $ini = INI::read('myfile.ini');
Write file : INI::write('myfile.ini', $ini);

Format of ini: $ini[section][key] = value or 
				$init[section][subsection1][subsection2....][key] = value for keys like general.conf.net.
				$ini[section][key][index] = value for array keys or
				

Features :
- support [] syntax for arrays
- support . in keys like bar.foo.something = value
- true and false string are automatically converted in booleans
- integers strings are automatically converted in integers
- keys are sorted when writing
- constants are replaced but they should be written in the ini file between braces : {MYCONSTANT}

*** KEEP this file silent (no trailing nothing !)
*/


class INI {

	/**
	 * PUBLIC Return the full ini file as a string
     */
	static function get_ini_string($ini) {
        $string = '';
        foreach(@array_keys($ini) as $key) {
            $string .= '['.$key."]\n";
            $string .= INI::write_get_string($ini[$key], '')."\n";
        }	
		return $string;
	}
    /**
     *  PUBLIC WRITE OUT INI TO FILE
     */
    static function write($filename, $ini) {

        return @file_put_contents($filename, INI::get_ini_string($ini));
    }
    /**
     *  write get string  : get string to write
     */
    static function write_get_string(& $ini, $prefix) {
        $string = '';
        ksort($ini);
        foreach($ini as $key => $val) {
            if (is_array($val)) {
                $string .= INI::write_get_string($ini[$key], $prefix.$key.'.');
            } else {
                $string .= $prefix.$key.' = '.str_replace("\n", "\\\n", INI::set_value($val))."\n";
            }
        }
        return $string;
    }
    /**
     *  set value : from native to strings
     */
    static function set_value($val) {
        if ($val === true) { return 'true'; }
        else if ($val === false) { return 'false'; }
        return $val;
    }
    /**
     *  PUBLIC READ OUT INI FILE
     */
    static function read($filename) {
        $ini = array();
        $lines = @file($filename);
        $section = 'default';
        $multi = '';
        foreach(@$lines as $line) {
            if (substr($line, 0, 1) !== ';') {
                $line = str_replace("\r", "", str_replace("\n", "", $line));
                if (preg_match('/^\[(.*)\]/', $line, $m)) {
                    $section = $m[1];
                } else if ($multi === '' && preg_match('/^([a-z0-9_.\[\]-]+)\s*=\s*(.*)$/i', $line, $m)) {
                    $key = $m[1];
                    $val = $m[2];
                    if (substr($val, -1) !== "\\") {
                        $val = trim($val);
                        INI::manage_keys($ini[$section], $key, $val);
                        $multi = '';
                    } else {
                        $multi = substr($val, 0, -1)."\n";
                    }
                } else if ($multi !== '') {
                    if (substr($line, -1) === "\\") {
                        $multi .= substr($line, 0, -1)."\n";
                    } else {
                        INI::manage_keys($ini[$section], $key, $multi.$line);
                        $multi = '';
                    }
                }
            }
        }
       
        $buf = get_defined_constants(true);
        $consts = array();
		if (@count($buf['user']))
	        foreach($buf['user'] as $key => $val) {
    	        $consts['{'.$key.'}'] = $val;
    	    }
        array_walk_recursive($ini, array('INI', 'replace_consts'), $consts);
        return $ini;
    }
    /**
     *  get value : from strings to native
     */
    static function get_value($val) {
        if (preg_match('/^-?[0-9]$/i', $val)) { return intval($val); }
        else if (strtolower($val) === 'true') { return true; }
        else if (strtolower($val) === 'false') { return false; }
        else if (preg_match('/^"(.*)"$/i', $val, $m)) { return $m[1]; }
        else if (preg_match('/^\'(.*)\'$/i', $val, $m)) { return $m[1]; }
        return $val;
    }
    /**
     *  get keys
     */
    static function get_key($val) {
        if (preg_match('/^[0-9]$/i', $val)) { return intval($val); }
        return $val;
    }
    /**
     *  manage keys
     */
    static function manage_keys(& $ini, $key, $val) {
        if (preg_match('/^([a-z0-9_-]+)\.(.*)$/i', $key, $m)) {
            INI::manage_keys($ini[$m[1]], $m[2], $val);
        } else if (preg_match('/^([a-z0-9_-]+)\[(.*)\]$/i', $key, $m)) {
            if ($m[2] !== '') {
                $ini[$m[1]][INI::get_key($m[2])] = INI::get_value($val);
            } else {
                $ini[$m[1]][] = INI::get_value($val);
            }
        } else {
            $ini[INI::get_key($key)] = INI::get_value($val);
        }
    }
    /**
     *  replace utility
     */
    static function replace_consts(& $item, $key, $consts) {
        if (is_string($item)) {
            $item = strtr($item, $consts);
        }
    }
}

?>