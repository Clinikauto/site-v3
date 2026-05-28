<?php
// Fallback redirect: if the server executes this file for requests to /assets/logo.png
header('Location: /assets/logo.avif', true, 301);
exit;
