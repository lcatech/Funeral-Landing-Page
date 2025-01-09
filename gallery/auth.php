<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
        header('Location: login.php');
        exit;
    }
}

function isLoggedIn() {
    return isset($_SESSION['admin']) && $_SESSION['admin'];
}