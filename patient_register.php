<?php
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = ($_POST["role"] ?? "patient") === "doctor" ? "doctor" : "patient";
    $name = trim($_POST["name"] ?? "");
    $age = (int)($_POST["age"] ?? 0);
    $gender = $_POST["gender"] ?? "";
    $bloodGroup = $role === "patient" ? trim($_POST["bloodGroup"] ?? "") : null;
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $rawPassword = $_POST["password"] ?? "";

    $specialization = $role === "doctor" ? trim($_POST["specialization"] ?? "") : null;
    $registrationNumber = $role === "doctor" ? trim($_POST["registrationNumber"] ?? "") : null;
    $experience = $role === "doctor" ? (int)($_POST["experience"] ?? 0) : null;
    $workplace = $role === "doctor" ? trim($_POST["workplace"] ?? "") : null;

    if ($name === "" || $age <= 0 || $gender === "" || $email === "" || $phone === "" || $address === "" || $rawPassword === "") {
        echo "<script>alert('Please fill all required fields');window.location='index.php';</script>";
        exit();
    }

    if ($role === "patient" && $bloodGroup === "") {
        echo "<script>alert('Please enter blood group');window.location='index.php';</script>";
        exit();
    }

    if ($role === "doctor" && ($specialization === "" || $registrationNumber === "" || $workplace === "")) {
        echo "<script>alert('Please fill all doctor details');window.location='index.php';</script>";
        exit();
    }

    // Extend the existing users table without deleting or changing existing patient columns/data.
    ensureColumn($conn, "users", "role", "VARCHAR(20) NOT NULL DEFAULT 'patient'");
    ensureColumn($conn, "users", "specialization", "VARCHAR(150) NULL");
    ensureColumn($conn, "users", "registrationNumber", "VARCHAR(150) NULL");
    ensureColumn($conn, "users", "experience", "INT NULL");
    ensureColumn($conn, "users", "workplace", "VARCHAR(255) NULL");

    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Email already registered');window.location='index.php';</script>";
        exit();
    }

    if ($role === "doctor") {
        $regCheck = $conn->prepare("SELECT id FROM users WHERE registrationNumber=? AND role='doctor'");
        $regCheck->bind_param("s", $registrationNumber);
        $regCheck->execute();
        $regCheck->store_result();

        if ($regCheck->num_rows > 0) {
            echo "<script>alert('Medical registration number already registered');window.location='index.php';</script>";
            exit();
        }
    }

    $password = password_hash($rawPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO users
        (name, age, gender, bloodGroup, email, phone, address, password, role, specialization, registrationNumber, experience, workplace)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "sisssssssssis",
        $name,
        $age,
        $gender,
        $bloodGroup,
        $email,
        $phone,
        $address,
        $password,
        $role,
        $specialization,
        $registrationNumber,
        $experience,
        $workplace
    );

    if ($stmt->execute()) {
        $message = $role === "doctor" ? "Doctor Registration Successful" : "Patient Registration Successful";
        echo "<script>alert('$message');window.location='index.php';</script>";
    } else {
        echo "<script>alert('Registration failed. Please try again.');window.location='index.php';</script>";
    }
}
?>