<?php

if (empty($_SESSION['admin_id'])) {
    json_out(['error' => 'No autorizado'], 401);
}
