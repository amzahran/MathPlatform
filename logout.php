<?php
session_start();

// مسح جميع بيانات الجلسة
$_SESSION = array();

// إذا كنت تريد حذف الكوكيز أيضا
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// تدمير الجلسة
session_destroy();

// التوجيه إلى صفحة الدخول
header("Location: login.html");
exit;
?>