
<?php
session_start();

require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, username, password, role
             FROM users
             WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Compatible with the current plain-text database passwords.
            if ($password === $user["password"]) {
                // Clear any previous customer or admin session data.
                session_regenerate_id(true);
                $_SESSION = [];

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];

                header("Location: dashboard.php");
                exit();
            }
        }

        $error = "Incorrect username or password.";
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>Admin Login | Heaven's Gift Bakeshop</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --brown: #402316;
            --brown-light: #65412d;
            --caramel: #c47b3b;
            --gold: #e8b878;
            --cream: #fbf6ef;
            --muted: #94867c;
            --line: #e9e1d8;
        }

        body {
            min-height: 100vh;
            padding: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 10% 10%, #f1dfc9 0, transparent 32%),
                #f7f1e9;
            color: var(--brown);
            font-family: "DM Sans", sans-serif;
        }

        .login-shell {
            width: 100%;
            max-width: 1120px;
            min-height: 650px;
            display: grid;
            grid-template-columns: 1fr 0.92fr;
            overflow: hidden;
            border-radius: 26px;
            background: #fff;
            box-shadow: 0 28px 80px rgba(61, 35, 20, 0.15);
            border: 1px solid rgba(255,255,255,.8);
        }

        /* LEFT: BAKESHOP DESCRIPTION */
        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 43px;
            overflow: hidden;
            color: white;
            background:
                linear-gradient(
                    180deg,
                    rgba(42, 24, 15, .80),
                    rgba(42, 24, 15, .94)
                ),
                url("https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=1200&q=85")
                center / cover;
        }

        .brand-panel::before {
            content: "";
            position: absolute;
            width: 330px;
            height: 330px;
            right: -170px;
            top: 100px;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 50%;
            box-shadow:
                0 0 0 35px rgba(255,255,255,.025),
                0 0 0 70px rgba(255,255,255,.02);
            pointer-events: none;
        }

        .brand-content,
        .brand-bottom {
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .brand-logo-icon {
            width: 49px;
            height: 49px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(255,255,255,.35);
            border-radius: 15px;
            background: rgba(255,255,255,.12);
            color: var(--gold);
            font-size: 21px;
        }

        .brand-logo h2 {
            font-family: "Playfair Display", serif;
            font-size: 21px;
            font-weight: 600;
        }

        .brand-logo p {
            margin-top: 3px;
            color: #e4d3c4;
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .brand-copy {
            margin-top: 85px;
            max-width: 410px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 20px;
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 27px;
            height: 1px;
            background: var(--gold);
        }

        .brand-copy h1 {
            margin-bottom: 20px;
            font-family: "Playfair Display", serif;
            font-size: clamp(36px, 4vw, 51px);
            font-weight: 600;
            line-height: 1.16;
            letter-spacing: -.7px;
        }

        .brand-copy h1 span {
            color: var(--gold);
            font-style: italic;
        }

        .brand-copy > p {
            max-width: 360px;
            color: #e3d8ce;
            font-size: 14px;
            line-height: 1.9;
        }

        .feature-list {
            display: grid;
            gap: 17px;
            margin-top: 35px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #f6eee7;
            font-size: 12px;
        }

        .feature-icon {
            width: 35px;
            height: 35px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border: 1px solid rgba(232,184,120,.3);
            border-radius: 11px;
            background: rgba(232,184,120,.11);
            color: var(--gold);
        }

        .brand-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: 48px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,.18);
            color: #d6c5b7;
            font-size: 11px;
        }

        .brand-bottom i {
            margin-right: 6px;
            color: var(--gold);
        }

        /* RIGHT: LOGIN FORM */
        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 54px;
            background: #fff;
        }

        .login-content {
            width: 100%;
            max-width: 365px;
        }

        .mobile-brand {
            display: none;
        }

        .form-heading {
            margin-bottom: 34px;
        }

        .form-heading .small-label {
            display: block;
            margin-bottom: 13px;
            color: var(--caramel);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .form-heading h2 {
            margin-bottom: 10px;
            color: #2f2018;
            font-family: "Playfair Display", serif;
            font-size: 35px;
            font-weight: 600;
            letter-spacing: -.6px;
        }

        .form-heading p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .error-message {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 22px;
            padding: 13px 14px;
            border: 1px solid #f2d2cd;
            border-radius: 11px;
            background: #fff5f3;
            color: #a83327;
            font-size: 12px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 21px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #49392e;
            font-size: 12px;
            font-weight: 700;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap > i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a99b90;
            font-size: 14px;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            height: 51px;
            padding: 0 44px 0 43px;
            outline: none;
            border: 1px solid var(--line);
            border-radius: 11px;
            background: #fdfbf9;
            color: var(--brown);
            font-family: inherit;
            font-size: 13px;
            transition: .2s ease;
        }

        .input-wrap input::placeholder {
            color: #b5aaa1;
        }

        .input-wrap input:focus {
            border-color: var(--caramel);
            background: white;
            box-shadow: 0 0 0 4px rgba(196,123,59,.10);
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 13px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #9a8b80;
            cursor: pointer;
            padding: 5px;
        }

        .toggle-password:hover {
            color: var(--caramel);
        }

        .login-button {
            width: 100%;
            height: 51px;
            margin-top: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(135deg, #bd783c, #8f4e27);
            color: white;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 9px 20px rgba(143,78,39,.18);
            transition: .2s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(143,78,39,.25);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .form-divider {
            display: flex;
            align-items: center;
            gap: 13px;
            margin: 25px 0;
            color: #b0a39a;
            font-size: 11px;
        }

        .form-divider::before,
        .form-divider::after {
            content: "";
            height: 1px;
            flex: 1;
            background: #eee7e0;
        }

        .store-link {
            width: 100%;
            min-height: 47px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 1px solid #e8d8c8;
            border-radius: 11px;
            color: #8d512b;
            background: #fffaf5;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s ease;
        }

        .store-link:hover {
            border-color: #c99568;
            background: #fff4e8;
        }

        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0eae4;
            color: #a3988f;
            font-size: 10px;
        }

        .security-note i {
            color: #b9824d;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            body {
                padding: 20px;
            }

            .login-shell {
                max-width: 720px;
                grid-template-columns: .9fr 1fr;
            }

            .brand-panel {
                padding: 32px;
            }

            .brand-copy {
                margin-top: 65px;
            }

            .brand-copy h1 {
                font-size: 37px;
            }

            .form-panel {
                padding: 38px 30px;
            }
        }

        @media (max-width: 650px) {
            body {
                padding: 15px;
                align-items: flex-start;
            }

            .login-shell {
                max-width: 440px;
                min-height: auto;
                margin: auto;
                grid-template-columns: 1fr;
                border-radius: 21px;
            }

            .brand-panel {
                min-height: 260px;
                padding: 25px;
                background-position: center 55%;
            }

            .brand-logo-icon {
                width: 43px;
                height: 43px;
            }

            .brand-logo h2 {
                font-size: 19px;
            }

            .brand-copy {
                margin-top: 38px;
            }

            .brand-copy .eyebrow {
                margin-bottom: 12px;
            }

            .brand-copy h1 {
                max-width: 340px;
                margin-bottom: 10px;
                font-size: 34px;
            }

            .brand-copy > p {
                font-size: 12px;
                line-height: 1.7;
            }

            .feature-list {
                display: none;
            }

            .brand-bottom {
                margin-top: 25px;
                padding-top: 15px;
                font-size: 10px;
            }

            .form-panel {
                padding: 32px 25px 26px;
            }

            .form-heading {
                margin-bottom: 25px;
            }

            .form-heading h2 {
                font-size: 30px;
            }

            .security-note {
                margin-top: 23px;
            }
        }

        @media (max-width: 360px) {
            .brand-copy h1 {
                font-size: 29px;
            }

            .form-panel {
                padding: 27px 19px;
            }

            .brand-bottom {
                flex-wrap: wrap;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition: none !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>

<body>
    <main class="login-shell">

        <section class="brand-panel">
            <div class="brand-content">
                <div class="brand-logo">
                    <div class="brand-logo-icon">
                        <i class="fa-solid fa-bread-slice"></i>
                    </div>
                    <div>
                        <h2>Heaven's Gift</h2>
                        <p>Freshly Baked with Love</p>
                    </div>
                </div>

                <div class="brand-copy">
                    <div class="eyebrow">Welcome to our bakeshop</div>

                    <h1>Freshly baked.<br><span>Made with love.</span></h1>

                    <p>
                        Welcome to Heaven's Gift Bakeshop, where every loaf,
                        pastry, and sweet treat is prepared with care.
                        Manage your daily operations and keep your bakery
                        running smoothly in one place.
                    </p>

                    <div class="feature-list">
                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fa-solid fa-wheat-awn"></i>
                            </div>
                            <span>Freshly prepared bakery products</span>
                        </div>

                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>
                            <span>Organized products and inventory</span>
                        </div>

                        <div class="feature">
                            <div class="feature-icon">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <span>Simple sales and business management</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="brand-bottom">
                <span><i class="fa-solid fa-location-dot"></i> Inabanga, Bohol</span>
                <span>Made with care, every day.</span>
            </div>
        </section>

        <section class="form-panel">
            <div class="login-content">

                <div class="mobile-brand">
                    <div class="brand-logo-icon">
                        <i class="fa-solid fa-bread-slice"></i>
                    </div>
                </div>

                <div class="form-heading">
                    <span class="small-label">Admin Portal</span>
                    <h2>Welcome back!</h2>
                    <p>Sign in to your account to manage your bakeshop.</p>
                </div>

                <?php if ($error !== ""): ?>
                    <div class="error-message" role="alert">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="username">Username</label>

                        <div class="input-wrap">
                            <i class="fa-regular fa-user"></i>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Enter your username"
                                autocomplete="off"
                                autocapitalize="none"
                                spellcheck="false"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>

                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                class="toggle-password"
                                type="button"
                                id="togglePassword"
                                aria-label="Show password"
                            >
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-button">
                        Sign In <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <div class="form-divider">OR</div>

                <a href="shop/index.php" class="store-link">
                    <i class="fa-solid fa-store"></i>
                    Visit Customer Store
                </a>

                <div class="security-note">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Authorized access for bakeshop management</span>
                </div>
            </div>
        </section>

    </main>

    <script>
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");

        togglePassword.addEventListener("click", function () {
            const isPassword = passwordInput.type === "password";

            passwordInput.type = isPassword ? "text" : "password";

            togglePassword.innerHTML = isPassword
                ? '<i class="fa-regular fa-eye-slash"></i>'
                : '<i class="fa-regular fa-eye"></i>';

            togglePassword.setAttribute(
                "aria-label",
                isPassword ? "Hide password" : "Show password"
            );
        });

        window.addEventListener("pageshow", function () {
            document.getElementById("username").value = "";
            passwordInput.value = "";
            passwordInput.type = "password";
            togglePassword.innerHTML = '<i class="fa-regular fa-eye"></i>';
        });
    </script>
</body>
</html>
