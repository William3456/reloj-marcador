<?php
if (! function_exists('remove_accents')) {
    function remove_accents($string)
    {
        return str_replace(
            ['Á','É','Í','Ó','Ú','Ñ','á','é','í','ó','ú','ñ'],
            ['A','E','I','O','U','N','a','e','i','o','u','n'],
            $string
        );
    }
}