<?php

use Slim\Http\Request;

class InputParser
{
    public static function parseRequest(Request $request): ?array
    {
        $contentType = $request->getHeaderLine('Content-Type');
        $contents = null;

        if (strstr($contentType, 'application/json')) {
            $contents = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
        }
        return $contents;
    }
}