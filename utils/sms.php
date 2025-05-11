<?php
function isValidPhoneNumber($phone) {
    return preg_match('/^\+251[79]\d{8}$/', $phone);
}