<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth.php';
require_guest();
$errors = [];
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? null;
    /*
    |--------------------------------------------------------------------------
    | CSRF validation
    |--------------------------------------------------------------------------
    */
    if (!verify_csrf_token(is_string($csrfToken) ? $csrfToken : null)) {
        $errors[] = 'Invalid request. Please refresh the page and try again.';
    }
    /*
    |--------------------------------------------------------------------------
    | Name validation
    |--------------------------------------------------------------------------
    */
    if ($name === '') {
        $errors[] = 'Please enter your full name.';
    } elseif (mb_strlen($name) < 2) {
        $errors[] = 'Your name must contain at least 2 characters.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = 'Your name cannot exceed 100 characters.';
    }
    /*
    |--------------------------------------------------------------------------
    | Email validation
    |--------------------------------------------------------------------------
    */
    if ($email === '') {
        $errors[] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (mb_strlen($email) > 150) {
        $errors[] = 'Your email address is too long.';
    }
    /*
    |--------------------------------------------------------------------------
    | Password validation
    |--------------------------------------------------------------------------
    */

    if ($password === '') {
        $errors[] = 'Please enter a password.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }

    if ($confirmPassword === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    /*
    |--------------------------------------------------------------------------
    | Create user account
    |--------------------------------------------------------------------------
    */
    if (empty($errors)) {
        try {
            $checkStatement = db()->prepare(
                'SELECT id
                 FROM users
                 WHERE email = ?
                 LIMIT 1'
            );

            $checkStatement->execute([$email]);

            if ($checkStatement->fetch()) {
                $errors[] = 'An account already exists with this email address.';
            } else {
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                if ($passwordHash === false) {
                    throw new RuntimeException(
                        'Unable to securely process the password.'
                    );
                }

                $insertStatement = db()->prepare(
                    'INSERT INTO users
                        (name, email, password, role, status)
                     VALUES
                        (?, ?, ?, ?, ?)'
                );

                $insertStatement->execute([
                    $name,
                    $email,
                    $passwordHash,
                    'user',
                    'active',
                ]);

                clear_old_input();

                set_flash(
                    'success',
                    'Registration successful. You can now log in.'
                );

                redirect('/movieverse/auth/login.php');
            }
        } catch (PDOException $error) {
            /*
             * MySQL duplicate email error.
             */
            if ((string) $error->getCode() === '23000') {
                $errors[] = 'An account already exists with this email address.';
            } else {
                $errors[] = 'Registration failed. Please check your database connection.';
            }
        } catch (Throwable $error) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Create Account | CineMatch';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Create your CineMatch movie recommendation account."
    >

    <title><?= e($pageTitle) ?></title>

    <style>
        :root {
            --background: #07070b;
            --surface: rgba(22, 22, 32, 0.88);
            --surface-light: rgba(255, 255, 255, 0.06);
            --border: rgba(255, 255, 255, 0.12);
            --text: #f7f7fb;
            --muted: #a9a9b8;
            --primary: #ff4d6d;
            --primary-dark: #d93658;
            --secondary: #8b5cf6;
            --danger: #ff6b6b;
            --success: #3ddc97;
            --shadow: 0 30px 70px rgba(0, 0, 0, 0.45);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            color: var(--text);
            background:
                radial-gradient(
                    circle at top left,
                    rgba(139, 92, 246, 0.22),
                    transparent 35%
                ),
                radial-gradient(
                    circle at bottom right,
                    rgba(255, 77, 109, 0.2),
                    transparent 35%
                ),
                var(--background);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input {
            font: inherit;
        }

        .page-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(420px, 0.95fr);
        }

        .hero-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            overflow: hidden;
            border-right: 1px solid var(--border);
        }

        .hero-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(
                    135deg,
                    rgba(255, 77, 109, 0.18),
                    transparent 40%
                );
            pointer-events: none;
        }

        .brand,
        .hero-content,
        .hero-footer {
            position: relative;
            z-index: 1;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            width: fit-content;
            font-weight: 800;
            letter-spacing: -0.03em;
            font-size: 1.35rem;
        }

        .brand-logo {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--secondary)
                );
            box-shadow: 0 12px 30px rgba(255, 77, 109, 0.28);
        }

        .hero-content {
            max-width: 650px;
            margin: 70px 0;
        }

        .eyebrow {
            display: inline-flex;
            padding: 8px 13px;
            margin-bottom: 22px;
            border: 1px solid var(--border);
            border-radius: 999px;
            color: #f7c9d2;
            background: var(--surface-light);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero-content h1 {
            max-width: 580px;
            margin-bottom: 22px;
            font-size: clamp(2.7rem, 6vw, 5.6rem);
            line-height: 0.95;
            letter-spacing: -0.065em;
        }

        .gradient-text {
            background:
                linear-gradient(
                    90deg,
                    #ff6b81,
                    #bd8cff,
                    #7dd3fc
                );
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero-content p {
            max-width: 570px;
            color: var(--muted);
            font-size: 1.08rem;
            line-height: 1.8;
        }

        .feature-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 32px;
        }

        .feature-chip {
            padding: 11px 15px;
            border: 1px solid var(--border);
            border-radius: 999px;
            color: #dadae4;
            background: var(--surface-light);
            font-size: 0.9rem;
        }

        .hero-footer {
            color: var(--muted);
            font-size: 0.88rem;
        }

        .form-panel {
            display: grid;
            place-items: center;
            padding: 40px 26px;
        }

        .form-card {
            width: min(100%, 500px);
            padding: 38px;
            border: 1px solid var(--border);
            border-radius: 28px;
            background: var(--surface);
            box-shadow: var(--shadow);
            backdrop-filter: blur(24px);
        }

        .form-header {
            margin-bottom: 28px;
        }

        .mobile-brand {
            display: none;
            margin-bottom: 30px;
        }

        .form-header h2 {
            margin-bottom: 10px;
            font-size: 2rem;
            letter-spacing: -0.045em;
        }

        .form-header p {
            color: var(--muted);
            line-height: 1.6;
        }

        .error-box {
            margin-bottom: 22px;
            padding: 16px 18px;
            border: 1px solid rgba(255, 107, 107, 0.35);
            border-radius: 16px;
            background: rgba(255, 107, 107, 0.1);
            color: #ffd3d3;
        }

        .error-box strong {
            display: block;
            margin-bottom: 8px;
        }

        .error-box ul {
            padding-left: 20px;
        }

        .error-box li + li {
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #e7e7ee;
            font-size: 0.92rem;
            font-weight: 700;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            min-height: 52px;
            padding: 0 16px;
            border: 1px solid var(--border);
            border-radius: 15px;
            outline: none;
            color: var(--text);
            background: rgba(255, 255, 255, 0.045);
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .input-wrapper input::placeholder {
            color: #747484;
        }

        .input-wrapper input:focus {
            border-color: rgba(255, 77, 109, 0.8);
            background: rgba(255, 255, 255, 0.065);
            box-shadow: 0 0 0 4px rgba(255, 77, 109, 0.12);
        }

        .password-input input {
            padding-right: 82px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            padding: 7px 9px;
            border: 0;
            border-radius: 9px;
            color: var(--muted);
            background: transparent;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .toggle-password:hover {
            color: var(--text);
            background: var(--surface-light);
        }

        .password-note {
            margin-top: 8px;
            color: #858596;
            font-size: 0.78rem;
            line-height: 1.5;
        }

        .submit-button {
            width: 100%;
            min-height: 54px;
            margin-top: 6px;
            border: 0;
            border-radius: 15px;
            color: white;
            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--primary-dark)
                );
            box-shadow: 0 14px 30px rgba(255, 77, 109, 0.25);
            font-weight: 800;
            cursor: pointer;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 36px rgba(255, 77, 109, 0.35);
        }

        .login-link {
            margin-top: 24px;
            color: var(--muted);
            text-align: center;
            font-size: 0.94rem;
        }

        .login-link a {
            color: #ff8299;
            font-weight: 800;
        }

        .home-link {
            display: block;
            margin-top: 18px;
            color: #858596;
            text-align: center;
            font-size: 0.88rem;
        }

        .home-link:hover {
            color: var(--text);
        }

        @media (max-width: 900px) {
            .page-shell {
                display: block;
            }

            .hero-panel {
                display: none;
            }

            .form-panel {
                min-height: 100vh;
                padding: 28px 18px;
            }

            .mobile-brand {
                display: inline-flex;
            }
        }

        @media (max-width: 520px) {
            .form-card {
                padding: 28px 20px;
                border-radius: 22px;
            }

            .form-header h2 {
                font-size: 1.7rem;
            }
        }
    </style>
</head>

<body>
<div class="page-shell">

    <section class="hero-panel">
        <a class="brand" href="/movieverse/">
            <span class="brand-logo">▶</span>
            <span>CineMatch</span>
        </a>

        <div class="hero-content">
            <span class="eyebrow">Movie Recommendation System</span>

            <h1>
                Find your next
                <span class="gradient-text">favourite movie.</span>
            </h1>

            <p>
                Create an account to build your personal movie profile,
                save recommendations and discover films that match your
                interests.
            </p>

            <div class="feature-row">
                <span class="feature-chip">250 movies</span>
                <span class="feature-chip">Mood recommendations</span>
                <span class="feature-chip">IMDb information</span>
                <span class="feature-chip">Personal dashboard</span>
            </div>
        </div>

        <p class="hero-footer">
            CineMatch — Movie Recommendation System
        </p>
    </section>

    <main class="form-panel">
        <div class="form-card">

            <a class="brand mobile-brand" href="/movieverse/">
                <span class="brand-logo">▶</span>
                <span>CineMatch</span>
            </a>

            <header class="form-header">
                <h2>Create your account</h2>

                <p>
                    Join CineMatch and start building your personalised
                    movie collection.
                </p>
            </header>

            <?php if (!empty($errors)): ?>
                <div class="error-box" role="alert">
                    <strong>Registration could not be completed:</strong>

                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="on">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="name">Full name</label>

                    <div class="input-wrapper">
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= e($name) ?>"
                            placeholder="Enter your full name"
                            maxlength="100"
                            autocomplete="name"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email address</label>

                    <div class="input-wrapper">
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= e($email) ?>"
                            placeholder="name@example.com"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="input-wrapper password-input">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Create a secure password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            class="toggle-password"
                            type="button"
                            data-target="password"
                            aria-label="Show password"
                        >
                            Show
                        </button>
                    </div>

                    <p class="password-note">
                        Use at least 8 characters with uppercase,
                        lowercase and a number.
                    </p>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm password</label>

                    <div class="input-wrapper password-input">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Enter the password again"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            class="toggle-password"
                            type="button"
                            data-target="confirm_password"
                            aria-label="Show password"
                        >
                            Show
                        </button>
                    </div>
                </div>

                <button class="submit-button" type="submit">
                    Create account
                </button>
            </form>

            <p class="login-link">
                Already have an account?
                <a href="/movieverse/auth/login.php">Log in</a>
            </p>
            <a class="home-link" href="/movieverse/">
                ← Return to movie recommendations
            </a>
        </div>
    </main>
</div>

<script>
    document.querySelectorAll('.toggle-password').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.dataset.target;
            const input = document.getElementById(targetId);

            if (!input) {
                return;
            }

            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Hide' : 'Show';
            button.setAttribute(
                'aria-label',
                isHidden ? 'Hide password' : 'Show password'
            );
        });
    });
</script>
                        </body>
                        </html>
                        