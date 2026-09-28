<?php

function setMessage($type, $message)
{
    $_SESSION["message_type"] = $type;
    $_SESSION["message"] = $message;
}


function displayMessage()
{
    if (!isset($_SESSION["message"])) {
        return;
    }

    $type = $_SESSION["message_type"] ?? "success";
    $message = $_SESSION["message"];

    echo '<div class="system-message ' . htmlspecialchars($type) . '">'
        . htmlspecialchars($message)
        . '</div>';

    unset($_SESSION["message"]);
    unset($_SESSION["message_type"]);
}