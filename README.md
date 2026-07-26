Steps for making Database :
1. Download Xampp
2. Open Xampp Control Pannel
3. Start Apache and SQL
4. Go to Admin of SQL
5. Create Database by pasting SQL Query ,which is given below

  -- Create Database
CREATE DATABASE IF NOT EXISTS register;
USE register;

-- ==========================
-- USERS TABLE (Patients)
-- ==========================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender VARCHAR(20) NOT NULL,
    bloodGroup VARCHAR(10),
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    password VARCHAR(255) NOT NULL,

    role VARCHAR(20) DEFAULT 'Patient',
    specialization VARCHAR(100) DEFAULT NULL,
    registrationNumber VARCHAR(100) DEFAULT NULL,
    experience INT DEFAULT NULL,
    workplace VARCHAR(150) DEFAULT NULL,

    profile_photo VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- DOCTORS TABLE
-- ==========================
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender VARCHAR(20) NOT NULL,

    specialization VARCHAR(100) NOT NULL,
    medical_registration_no VARCHAR(100) NOT NULL UNIQUE,

    experience INT DEFAULT 0,
    hospital VARCHAR(150),

    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,

    password VARCHAR(255) NOT NULL,

    availability VARCHAR(10) NOT NULL DEFAULT 'No',
    profile_photo VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================
-- APPOINTMENTS TABLE
-- ==========================
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    doctor_name VARCHAR(100) NOT NULL,
    speciality VARCHAR(100),

    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,

    reason TEXT,

    status VARCHAR(30) NOT NULL DEFAULT 'Pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- ==========================
-- DOCTOR RECOMMENDATIONS
-- ==========================
CREATE TABLE IF NOT EXISTS doctor_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,

    doctor_id INT NOT NULL,
    user_id INT NOT NULL,

    recommendation TEXT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX(user_id),
    INDEX(doctor_id),

    FOREIGN KEY (doctor_id)
        REFERENCES doctors(id)
        ON DELETE CASCADE,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);
