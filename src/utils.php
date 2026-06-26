<?php

/**
 * Get a value from an array by key, with a default value if the key is not set.
 * 
 * @param array $arr
 * @param string $key
 * @param mixed $default
 * 
 * @return mixed
 */
function array_get_default(array $arr, string $key, $default=null)
{
    if (! isset($arr[$key])) return $default;
    return $arr[$key];
}
