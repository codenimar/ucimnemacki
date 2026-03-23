<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }
        if (!verifyToken($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false,'message'=>'Nevažeći CSRF token.']); exit; }
        $result = loginUser(trim($_POST['username'] ?? ''), $_POST['password'] ?? '');
        echo json_encode($result);
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }
        if (!verifyToken($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false,'message'=>'Nevažeći CSRF token.']); exit; }
        $result = registerUser(trim($_POST['username'] ?? ''), trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
        echo json_encode($result);
        break;

    case 'logout':
        logoutUser();
        header('Location: ' . SITE_URL . '/index.php');
        exit;

    case 'google':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'POST required']); exit; }
        $idToken = $_POST['id_token'] ?? '';
        if (!$idToken) { echo json_encode(['success'=>false,'message'=>'Nedostaje token.']); exit; }
        $result = googleLogin($idToken);
        echo json_encode($result);
        break;

    case 'me':
        if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Niste prijavljeni.']); exit; }
        $user = getCurrentUser();
        unset($user['password_hash']);
        echo json_encode(['success'=>true,'user'=>$user]);
        break;

    default:
        echo json_encode(['error'=>'Nepoznata akcija.']);
}
