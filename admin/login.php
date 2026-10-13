<?php
// TAMBAHKIN INI DI BARIS PALING ATAS
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

if(isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Simple auth
    if($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Login gagal! Username: admin, Password: admin123";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Harmoni Music Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        body { 
            background: #f5f6fa; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh;
            margin: 0;
        }
        .login-container { 
            background: white; 
            padding: 3rem; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .logo { 
            margin-bottom: 2rem; 
            color: #2c3e50;
        }
        .logo i { 
            font-size: 3rem; 
            color: #3498db; 
            margin-bottom: 1rem;
        }
        .logo h1 { 
            font-size: 1.8rem; 
            font-weight: 700; 
        }
        .form-group { 
            margin-bottom: 1.5rem; 
            text-align: left;
        }
        label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 600;
            color: #2c3e50;
        }
        input { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #ecf0f1; 
            border-radius: 8px; 
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        .btn { 
            width: 100%; 
            padding: 14px; 
            background: #3498db; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 1.5rem;
            border-left: 4px solid #e74c3c;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-top: 1.5rem;
            border-left: 4px solid #3498db;
            font-size: 0.9rem;
        }
        .info strong {
            display: block;
            margin-bottom: 5px;
        }
        .info code {
            background: rgba(255,255,255,0.5);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
        .login-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #ecf0f1;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-music"></i>
            <h1>Harmoni Admin</h1>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username
                </label>
                <input type="text" id="username" name="username" value="admin" required 
                       placeholder="Masukkan username">
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" id="password" name="password" value="admin123" required 
                       placeholder="Masukkan password">
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="info">
            <strong><i class="fas fa-info-circle"></i> Default Login</strong>
            Username: <code>admin</code><br>
            Password: <code>admin123</code>
        </div>

        <div class="login-footer">
            <i class="fas fa-shield-alt"></i> Harmoni Music Store Admin Panel
        </div>
    </div>
</body>
</html>