<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth.php';
require_guest();
$errors = [];
$email = '';
$flash = get_flash();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
        $errors[] = 'Invalid request. Refresh the page and try again.';
    }
    if ($email === '') {
        $errors[] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }
    if (empty($errors)) {
        try {
            $statement = db()->prepare(
                'SELECT
                    id,
                    name,
                    email,
                    password,
                    role,
                    status
                 FROM users
                 WHERE email = ?
                 LIMIT 1'
            );
            $statement->execute([$email]);
            $user = $statement->fetch();
            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Incorrect email address or password.';
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'Your account has been blocked.';
            } else {
                login_user($user);

                set_flash(
                    'success',
                    'Welcome back, ' . $user['name'] . '!'
                );

                redirect('/movieverse/user/dashboard.php');
            }
        } catch (PDOException $error) {
            $errors[] = 'Login failed. Please check the database connection.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Login | CineMatch</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            color: #ffffff;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(139, 92, 246, 0.3),
                    transparent 35%
                ),
                radial-gradient(
                    circle at bottom right,
                    rgba(255, 77, 109, 0.25),
                    transparent 35%
                ),
                #07070b;
        }
        .login-card {
            width: 100%;
            max-width: 460px;
            padding: 36px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 26px;
            background: rgba(22, 22, 32, 0.92);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.5);
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            color: white;
            font-size: 1.3rem;
            font-weight: 800;
            text-decoration: none;
        }
        .brand-logo {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 13px;
            background: linear-gradient(135deg, #ff4d6d, #8b5cf6);
        }
        h1 {
            margin: 0 0 10px;
            font-size: 2rem;
        }
        .subtitle {
            margin: 0 0 26px;
            color: #aaaaba;
            line-height: 1.6;
        }
        .message {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 14px;
            line-height: 1.5;
        }
        .message.success {
            border: 1px solid rgba(61, 220, 151, 0.35);
            color: #baf5d8;
            background: rgba(61, 220, 151, 0.1);
        }
        .message.error {
            border: 1px solid rgba(255, 107, 107, 0.35);
            color: #ffd1d1;
            background: rgba(255, 107, 107, 0.1);
        }

        .message ul {
            margin: 0;
            padding-left: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.92rem;
            font-weight: 700;
        }

        input {
            width: 100%;
            min-height: 52px;
            padding: 0 15px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 14px;
            outline: none;
            color: white;
            background: rgba(255, 255, 255, 0.05);
        }

        input:focus {
            border-color: #ff4d6d;
            box-shadow: 0 0 0 4px rgba(255, 77, 109, 0.12);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 75px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            border: 0;
            color: #aaaaba;
            background: transparent;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .login-button {
            width: 100%;
            min-height: 52px;
            border: 0;
            border-radius: 14px;
            color: white;
            background: linear-gradient(135deg, #ff4d6d, #d93658);
            font-weight: 800;
            cursor: pointer;
        }

        .register-text,
        .home-link {
            margin-top: 22px;
            color: #aaaaba;
            text-align: center;
        }

        .register-text a {
            color: #ff8299;
            font-weight: 700;
            text-decoration: none;
        }

        .home-link {
            display: block;
            font-size: 0.9rem;
            text-decoration: none;
        }
    </style>
</head>

<body>
<main class="login-card">
    <a class="brand" href="/movieverse/">
        <span class="brand-logo">▶</span>
        <span>CineMatch</span>
    </a>

    <h1>Welcome back</h1>

    <p class="subtitle">
        Log in to access your CineMatch account and movie dashboard.
    </p>

    <?php if ($flash): ?>
        <div class="message <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="email">Email address</label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?= e($email) ?>"
                placeholder="name@example.com"
                autocomplete="email"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>

            <div class="password-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    class="toggle-password"
                    id="togglePassword"
                >
                    Show
                </button>
            </div>
        </div>

        <button type="submit" class="login-button">
            Log in
        </button>
    </form>

    <p class="register-text">
        Don’t have an account?
        <a href="/movieverse/auth/register.php">Register</a>
    </p>

    <a class="home-link" href="/movieverse/">
        ← Return to movie recommendations
    </a>
</main>

<script>
    const passwordInput = document.getElementById('password');
    const toggleButton = document.getElementById('togglePassword');

    toggleButton.addEventListener('click', () => {
        const passwordHidden = passwordInput.type === 'password';

        passwordInput.type = passwordHidden ? 'text' : 'password';
        toggleButton.textContent = passwordHidden ? 'Hide' : 'Show';
    });
</script>
</body>
</html>