<<<<<<< HEAD
<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row) {
                $_SESSION['username'] = $row['username'];
                $_SESSION['just_logged_in'] = true;
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Invalid Input";
            }
        } else {
            $error_message = "Failed to execute query.";
        }

        $stmt->close();
    } else {
        $error_message = "Database connection error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <div class="wrapper" style="margin-top: 20px;">
        <h2><img src="../assets/hero.png" alt="Logo" class="logo"></h2>
        <form method="POST" action="login.php">
            <?php if (isset($error_message)): ?>
                <div style="color: red; margin-bottom: 10px; text-align: center;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="input-box">
                <input type="text" name="username" placeholder="Username" required>
                <i class='bx bxs-user'></i>
            </div>

            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>

            <div>
                <button type="submit" class="btn">Login</button>
            </div>
        </form>
        <div class="register-link">
            <p>Forgot Password? <a href="#">Recover Account!</a></p>
        </div>
    </div>

</body>

=======
<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
        $stmt->bind_param("ss", $username, $password);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row) {
                $_SESSION['username'] = $row['username'];
                $_SESSION['just_logged_in'] = true;
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Invalid Input";
            }
        } else {
            $error_message = "Failed to execute query.";
        }

        $stmt->close();
    } else {
        $error_message = "Database connection error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <div class="wrapper" style="margin-top: 20px;">
        <h2><img src="../assets/hero.png" alt="Logo" class="logo"></h2>
        <form method="POST" action="login.php">
            <?php if (isset($error_message)): ?>
                <div style="color: red; margin-bottom: 10px; text-align: center;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="input-box">
                <input type="text" name="username" placeholder="Username" required>
                <i class='bx bxs-user'></i>
            </div>

            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <i class='bx bxs-lock-alt'></i>
            </div>

            <div>
                <button type="submit" class="btn">Login</button>
            </div>
        </form>
        <div class="register-link">
            <p>Forgot Password? <a href="#">Recover Account!</a></p>
        </div>
    </div>

</body>

>>>>>>> 9348e80bcb55502c1bd7f8679ececfa5f24f0432
</html>