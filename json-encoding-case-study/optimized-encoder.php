 <?php

 function jsonEncodeUTFnormalWpf($value) {
    return json_encode($value,
        JSON_UNESCAPED_UNICODE |
        JSON_PARTIAL_OUTPUT_ON_ERROR |
        JSON_INVALID_UTF8_SUBSTITUTE
    ) ?: '';
}
