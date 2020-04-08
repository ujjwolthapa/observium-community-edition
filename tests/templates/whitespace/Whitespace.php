<?php

/**
 * Whitespace test for tag names.
 *
 * Per http://github.com/janl/mustache.js/issues/issue/34/#comment_244396
 * tags should strip leading and trailing whitespace in key names.
 *
 * `{{> tag }}` and `{{> tag}}` and `{{>tag}}` should all be equivalent.
 */

$arr = array(
          'foo' => 'alpha',
          'bar' => 'beta',
          'baz' => 'gamma',
          'qux' => array(
                    array('key with space' => 'A'),
                    array('key with space' => 'B'),
                    array('key with space' => 'C'),
                    array('key with space' => 'D'),
                    array('key with space' => 'E'),
                    array('key with space' => 'F'),
                    array('key with space' => 'G'),
                   ));

echo json_encode($arr);

