<?php
// SMS wrapper - implement provider API (Twilio, Nexmo etc.)
function send_sms($to, $message) {
    // TODO: integrate with provider SDK/HTTP API
    // Example: call provider endpoint using curl
    return ['status'=>'queued', 'to'=>$to];
}
