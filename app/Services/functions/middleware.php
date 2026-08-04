<?php

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

