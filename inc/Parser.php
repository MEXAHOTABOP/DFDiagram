<?php

class DFDParserError extends Exception { }

class DFDParser {
    public function tokenize ($text) {
        $tokens = array();
        $cur_token = '';
        $depth = 0;
        $index = 0;
        $length = mb_strlen($text);
        print($text);
        while ($index < $length) {
            $ch = mb_substr($text, $index, 1);
            $prev_ch = ($index > 0) ? mb_substr($text, $index - 1, 1) : '';
            if ($ch == '\\' && $prev_ch != '\\') {
                // escapes next character - pass
            }
            elseif ($ch == '[' && $prev_ch != '\\') {
                $depth++;
            }
            elseif ($ch == ']' && $prev_ch != '\\') {
                $depth--;
                if ($depth < 0)
                    throw new DFDParserError('Unmatched closing bracket');
            }
            $cur_token .= $ch;
            if ($depth == 0) {
                $tokens[] = $cur_token;
                $cur_token = '';
            }
            $index++;
        }
        if ($depth > 0)
            throw new DFDParserError('Unmatched opening bracket');
        return $tokens;
    }
    public function parse ($text) {
        $tokens = $this->tokenize($text);
    }
}
