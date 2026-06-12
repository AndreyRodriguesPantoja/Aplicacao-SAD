<?php

session_start();
session_destroy();

header("Location: login-screen.html");
exit;