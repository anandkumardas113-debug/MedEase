<?php
session_start();
include "config.php";

$user = null;

if (isset($_SESSION['user_id'])) {

    $id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

}
$recommendations = [];
if ($user) {
    $rs = $conn->prepare("SELECT r.recommendation,r.created_at,d.full_name doctor_name,d.specialization FROM doctor_recommendations r JOIN doctors d ON d.id=r.doctor_id WHERE r.user_id=? ORDER BY r.created_at DESC");
    $rs->bind_param('i',$id); $rs->execute(); $recommendations=$rs->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<?php
$appointments = [];

if (isset($_SESSION['user_id'])) {

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT * FROM Appointments WHERE user_id=? ORDER BY appointment_date DESC");

    $stmt->bind_param("i", $user_id);

    $stmt->execute();

    $appointments = $stmt->get_result();

}

// Load registered doctors from the database for doctor cards and search.
$registered_doctors = [];
$doctor_result = $conn->query("SELECT id, full_name, age, gender, specialization, medical_registration_no, experience, hospital, email, phone, address, profile_photo, availability FROM doctors ORDER BY id DESC");
if ($doctor_result) {
    while ($doctor = $doctor_result->fetch_assoc()) {
        $registered_doctors[] = [
            'id' => 'db_' . $doctor['id'],
            'name' => $doctor['full_name'],
            'type' => $doctor['specialization'],
            'exp' => (int) $doctor['experience'],
            'workplace' => $doctor['hospital'],
            'rating' => 'New',
            'img' => !empty($doctor['profile_photo']) ? '<img src="'.htmlspecialchars($doctor['profile_photo'], ENT_QUOTES).'" alt="Doctor photo" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">' : ($doctor['gender'] === 'Female' ? '👩‍⚕️' : '👨‍⚕️'),
            'time' => (($doctor['availability'] ?? 'No') === 'Yes' ? 'Available now' : 'Currently unavailable'),
            'email' => $doctor['email'],
            'phone' => $doctor['phone'],
            'address' => $doctor['address'],
            'registrationNo' => $doctor['medical_registration_no'],
            'gender' => $doctor['gender'],
            'age' => (int) $doctor['age'],
            'isRegistered' => true
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedEase - Healthcare Portal</title>
    <!-- Tailwind CSS for modern styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="style.css">

</head>

<body class="text-slate-900">

    <!-- AUTH MODAL -->
    <div id="auth-modal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 hidden">
        <div class="auth-card bg-white w-full max-w-lg rounded-[2rem] shadow-2xl relative">
            <button type="button" onclick="toggleAuthModal()"
                class="absolute top-4 right-4 z-10 text-gray-400 hover:text-gray-600">
                <i data-lucide="x"></i>
            </button>

            <div class="p-6 sm:p-7">
                <!-- LOGIN WRAPPER -->
                <div id="login-form" class="mx-auto max-w-sm">
                    <div class="text-center mb-4">
                        <h2 class="text-2xl font-bold mb-1">Welcome Back</h2>
                        <p class="text-sm text-gray-500">Choose your account type to login</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 bg-gray-100 p-1 rounded-xl mb-4">
                        <button type="button" id="patient-login-tab" onclick="switchLoginType('patient')"
                            class="register-type-tab active px-3 py-2 rounded-lg text-sm font-semibold">
                            Patient
                        </button>
                        <button type="button" id="doctor-login-tab" onclick="switchLoginType('doctor')"
                            class="register-type-tab px-3 py-2 rounded-lg text-sm font-semibold">
                            Doctor
                        </button>
                    </div>

                    <form id="patient-login-form" action="patient_login.php" method="post">
                        <div class="space-y-3">
                            <input name="email" type="email" placeholder="Email Address" required
                                class="auth-input w-full px-4 py-3 bg-gray-50 border rounded-xl">
                            <input name="password" type="password" placeholder="Password" required
                                class="auth-input w-full px-4 py-3 bg-gray-50 border rounded-xl">
                            <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold">
                                Login as Patient
                            </button>
                        </div>
                    </form>

                    <form id="doctor-login-form" action="doctor_login.php" method="post" class="hidden">
                        <div class="space-y-3">
                            <input name="medical_registration_no" type="text" placeholder="Medical Registration ID"
                                required class="auth-input w-full px-4 py-3 bg-gray-50 border rounded-xl">
                            <input name="password" type="password" placeholder="Password" required
                                class="auth-input w-full px-4 py-3 bg-gray-50 border rounded-xl">
                            <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold">
                                Login as Doctor
                            </button>
                        </div>
                    </form>

                    <p class="text-center mt-5 text-sm text-gray-500">
                        Don't have an account?
                        <a onclick="switchAuthMode('register')"
                            class="text-indigo-600 font-bold cursor-pointer">Register</a>
                    </p>
                </div>

                <!-- REGISTER WRAPPER -->
                <div id="signup-form" class="hidden">
                    <div class="text-center mb-4">
                        <h2 class="text-2xl font-bold mb-1">Create Account</h2>
                        <p class="text-sm text-gray-500">Choose your account type</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 bg-gray-100 p-1 rounded-xl mb-4">
                        <button type="button" id="patient-register-tab" onclick="switchRegisterType('patient')"
                            class="register-type-tab active px-3 py-2 rounded-lg text-sm font-semibold">
                            Patient
                        </button>
                        <button type="button" id="doctor-register-tab" onclick="switchRegisterType('doctor')"
                            class="register-type-tab px-3 py-2 rounded-lg text-sm font-semibold">
                            Doctor
                        </button>
                    </div>

                    <!-- PATIENT FORM: all original patient inputs retained -->
                    <form id="patient-signup-form" action="patient_register.php" method="post">
                        <input type="hidden" name="role" value="patient">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <input name="name" type="text" placeholder="Full Name" required
                                class="auth-input sm:col-span-2 w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="age" type="number" placeholder="Age" min="1" required
                                class="auth-input w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="bloodGroup" type="text" placeholder="Blood Group" required
                                class="auth-input w-full px-3 py-2.5 bg-gray-50 border rounded-xl">

                            <div class="sm:col-span-2 flex flex-wrap items-center gap-4 px-1 text-sm text-gray-600">
                                <span class="font-medium">Gender:</span>
                                <label><input type="radio" name="gender" value="Male" required> Male</label>
                                <label><input type="radio" name="gender" value="Female"> Female</label>
                                <label><input type="radio" name="gender" value="Other"> Other</label>
                            </div>

                            <input name="email" type="email" placeholder="Email Address" required
                                class="auth-input w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="phone" type="tel" placeholder="Phone Number" required
                                class="auth-input w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="address" type="text" placeholder="Address" required
                                class="auth-input sm:col-span-2 w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="password" type="password" placeholder="Password" required
                                class="auth-input sm:col-span-2 w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                        </div>
                        <button type="submit"
                            class="w-full mt-4 py-3 bg-indigo-600 text-white rounded-xl font-bold">Register as
                            Patient</button>
                    </form>

                    <!-- DOCTOR FORM -->
                    <form id="doctor-signup-form" action="doctor_register.php" method="POST" class="hidden">
                        <input type="hidden" name="role" value="doctor">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <input name="full_name" type="text" placeholder="Full Name" required
                                class="auth-input sm:col-span-2 w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="age" type="number" placeholder="Age" min="18" required
                                class="auth-input w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="specialization" type="text" placeholder="Specialization" required
                                class="auth-input w-full px-3 py-2.5 bg-gray-50 border rounded-xl">

                            <div class="sm:col-span-2 flex flex-wrap items-center gap-4 px-1 text-sm text-gray-600">
                                <span class="font-medium">Gender:</span>
                                <label><input type="radio" name="gender" value="Male" required> Male</label>
                                <label><input type="radio" name="gender" value="Female"> Female</label>
                                <label><input type="radio" name="gender" value="Other"> Other</label>
                            </div>

                            <input name="medical_registration_no" type="text" placeholder="Medical Registration No."
                                required class="auth-input w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="experience" type="number" placeholder="Experience (Years)" min="0" required
                                class="auth-input w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="hospital" type="text" placeholder="Hospital / Clinic" required
                                class="auth-input sm:col-span-2 w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="email" type="email" placeholder="Email Address" required
                                class="auth-input w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="phone" type="tel" placeholder="Phone Number" required
                                class="auth-input w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="address" type="text" placeholder="Address" required
                                class="auth-input sm:col-span-2 w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                            <input name="password" type="password" placeholder="Password" required
                                class="auth-input sm:col-span-2 w-full px-3 py-2.5 bg-gray-50 border rounded-xl">
                        </div>
                        <button type="submit"
                            class="w-full mt-4 py-3 bg-indigo-600 text-white rounded-xl font-bold">Register as
                            Doctor</button>
                    </form>

                    <p class="text-center mt-4 text-sm text-gray-500">
                        Already have an account?
                        <a onclick="switchAuthMode('login')" class="text-indigo-600 font-bold cursor-pointer">Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- NAVIGATION SECTION -->
    <header class="sticky top-0 z-50">
        <!-- Section 1: Contact Bar -->
        <div class="upperheader ">
            <div class="upperheader-1">
                <span class="upperheader-2"><i data-lucide="phone" class="w-3 h-3"></i> +91 9876543210</span>

            </div>

            <div class="upperheader-3 flex items-center gap-3">
                <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer"
                    class="text-slate-700 hover:text-white transition hover:scale-110">
                    <img src="instagram.png" alt="instagram" width="20">

                </a>

                <a href="https://x.com/" target="_blank" rel="noopener noreferrer"
                    class="text-slate-700 hover:text-white transition hover:scale-110">
                    <img src="twitter.webp " alt="twitter" width="20">

                </a>

                <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer"
                    class="text-slate-700 hover:text-white transition hover:scale-110">
                    <img src="facebook.png" alt="facebook" width="20">
                </a>
            </div>
        </div>

        <!-- Section 2: Main Nav -->
        <nav class=" bg-white/90 shadow-md py-4 px-6 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center gap-3 cursor-pointer group" onclick="navigateTo('home')">
                    <img src="logo.jpeg" class="w-10 h-10 group-hover:scale-110 transition-transform"
                        alt="MedEase Logo">
                    <span
                        class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600">MedEase</span>
                </div>

                <div class="hidden md:flex items-center gap-8 font-medium text-gray-600">
                    <button onclick="navigateTo('home')" class="nav-link hover:text-indigo-600 transition"
                        id="nav-home">Home</button>
                    <button onclick="navigateTo('doctors')" class="nav-link hover:text-indigo-600 transition"
                        id="nav-doctors">Doctors</button>
                    <button onclick="navigateTo('profile')" class="nav-link hover:text-indigo-600 transition"
                        id="nav-profile"> My Profile</button>
                </div>

                <div id="auth-section" class="flex items-center gap-3">
                    <button onclick="toggleAuthModal(); switchAuthMode('login')"
                        class="px-4 py-2 text-sm font-semibold text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition">Login</button>
                    <button onclick="toggleAuthModal(); switchAuthMode('register')"
                        class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Register</button>
                </div>
            </div>
        </nav>
    </header>

    <!-- HOME PAGE -->
    <main id="page-home" class="page-content page-active" opacity="0.6">
        <!-- Section 3: Hero & Search -->

        <section
            class="relative min-h-[620px] flex flex-col items-center justify-center px-6 py-20 overflow-hidden hero1 ">

            <div class="absolute top-10 left-10 w-32 h-32 bg-indigo-200/30 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-64 h-64 bg-purple-200/30 rounded-full blur-3xl animate-pulse">
            </div>

            <h1 class="text-4xl md:text-7xl font-extrabold text-center mb-6 leading-tight">
                Health, <br />
                <span class="text-indigo-600 italic">Made Simple.</span>
            </h1>
            <p class="  text-bold-black-700 text-lg mb-10 max-w-lg text-center font-light">
                Find and book the best doctors with a single click. High quality care is just a search away.
            </p>

            <div class="w-full max-w-2xl relative group">
                <div
                    class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-1000">
                </div>
                <div class="relative flex items-center bg-white rounded-2xl shadow-2xl p-2 border border-gray-100">
                    <i data-lucide="search" class="ml-4 text-dark-gray-400"></i>
                    <input type="text" placeholder="Search Your Doctor.." class="w-full px-4 py-4 outline-none text-lg"
                        id="home-search-input">
                    <button onclick="searchFromHome()"
                        class="bg-indigo-600 text-white px-8 py-4 rounded-xl font-bold hover:bg-indigo-700 transition transform active:scale-95 shadow-lg">
                        Search
                    </button>

                </div>
            </div>

        </section>




        <!-- <section class="  relative min-h-[500px] flex flex-col items-center justify-center px-6 py-20 overflow-hidden" >
            <div class="absolute top-10 left-10 w-32 h-32 bg-indigo-200/30 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-10 right-10 w-64 h-64 bg-purple-200/30 rounded-full blur-3xl animate-pulse"></div>
            
            <h1 class="text-4xl md:text-7xl font-extrabold text-center mb-6 leading-tight">
                 Health, <br />
                <span class="text-indigo-600 italic">Made Simple.</span>
            </h1>
            <p class="text-gray-500 text-lg mb-10 max-w-lg text-center font-light">
                Find and book the best doctors with a single click. High quality care is just a search away.
            </p>
            
            <div class="w-full max-w-2xl relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-1000"></div>
                <div class="relative flex items-center bg-white rounded-2xl shadow-2xl p-2 border border-gray-100">
                    <i data-lucide="search" class="ml-4 text-gray-400"></i>
                    <input type="text" placeholder="Search Your Doctor..." class="w-full px-4 py-4 outline-none text-lg" id="home-search-input">
                    <button onclick="navigateTo('doctors')" class="bg-indigo-600 text-white px-8 py-4 rounded-xl font-bold hover:bg-indigo-700 transition transform active:scale-95 shadow-lg">
                        Search
                    </button>
                </div>
            </div>
        </section> -->

        <!-- Section 4: Famous Doctors -->
        <section class="max-w-7xl mx-auto py-20 px-6 bg-indigo-100 boder-radius-10px shadow-lg">
            <div class="flex justify-between items-end mb-10">
                <div>
                    <h2 class="text-3xl font-bold mb-2">Recommends</h2>
                    <div class="w-20 h-1 bg-indigo-600 rounded-full"></div>
                </div>
                <button onclick="navigateTo('doctors')"
                    class="text-indigo-600 font-semibold flex items-center gap-1 hover:gap-2 transition-all">
                    See All <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8" id="famous-doctors-grid">
                <!-- Dynamic Content -->
            </div>
        </section>
    </main>

    <!-- DOCTOR PROFILE PAGE -->
    <main id="page-doctor" class="page-content max-w-5xl mx-auto py-10 px-6">
        <div id="doctor-profile-container">
            <!-- Populated dynamically via script.openDoctorProfile(id) -->
            <div class="text-center py-24 text-gray-500">
                <p class="text-xl">Select a doctor to view full profile.</p>
            </div>
        </div>
    </main>

    <!-- DOCTORS PAGE -->
    <main id="page-doctors" class="page-content max-w-7xl mx-auto py-10 px-6">
        <div class="flex flex-col lg:flex-row gap-10">
            <!-- Type of Doctors Sidebar -->
            <aside class="w-full lg:w-64 space-y-2">
                <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <i data-lucide="activity" class="text-indigo-600"></i> Specialities
                </h3>
                <div id="category-list" class="space-y-2">
                    <button onclick="filterDoctors('All')"
                        class="category-btn w-full text-left px-4 py-3 rounded-xl transition-all bg-indigo-700 text-white shadow-lg translate-x-2   ">All
                        Specialities</button>
                </div>
            </aside>

            <!-- Doctors Grid -->
            <div class="flex-1 bg-indigo-100 p-8 rounded-[2.5rem] shadow-2xl border border-gray-100">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-2xl font-bold">Find Your Doctor</h2>
                    <span class="text-sm text-gray-500" id="result-count">0 results found</span>
                </div>
                <div class="mb-6">
                    <label for="doctor-search-input" class="mb-2 block text-sm font-semibold text-gray-700">Search
                        doctors</label>
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input id="doctor-search-input" type="text" placeholder="Search by name, specialty or hospital"
                            class="w-full rounded-2xl border border-gray-200 bg-white py-3 pl-11 pr-4 outline-none ring-0 focus:border-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="doctors-main-grid">
                    <!-- Dynamic Content -->
                </div>
            </div>
        </div>
    </main>

    <!-- PROFILE PAGE -->
    <main id="page-profile" action ="patient_register.php" method="POST" class="page-content max-w-5xl mx-auto py-10 px-6">
        <div class="bg-indigo-100 rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden">
            <div class="h-51 bg-gradient-to-r from-indigo-600 to-purple-700 relative">
                <div class="bottom-0 left-12 flex items-end gap-6" style="z-index: 10;">
                    <div class="w-32 h-32 rounded-3xl bg-white p-1 shadow-2xl border border-gray-100">
                        <?php if (!empty($user['profile_photo'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile photo" class="w-full h-full rounded-[1.25rem] object-cover">
                            <?php else: ?>
                            <div class="w-full h-full bg-gray-100 rounded-[1.25rem] flex items-center justify-center text-5xl">👤</div>
                            <?php endif; ?>
                    </div>
                    <div class="mb-4">
                        <h2 class="text-3xl font-bold text-white"><span
                                id="profile-name"><?= $user['name'] ?? '' ?></span></h2>
                        <p class="text-white font-medium">Patient ID: <span
                                id="profile-ID"><?= $user['id'] ?? '' ?></span></p>
                    </div>
                </div>
            </div>

            <div class="mt-15 p-8 grid grid-cols-1 lg:grid-cols-2 gap-12">

                <div class="space-y-6">
                    <div  >
                        <h3 class="text-2xl font-bold mb-6 flex items-center gap-2">
                            <i data-lucide="user" class="text-indigo-600 w-6 h-6"></i> Personal Information
                        </h3>
                        <ul class="space-y-4 text-gray-700" >
                            <li><strong>Name:</strong> <span id="profile-name-brief"><?= $user['name'] ?? '' ?></span>
                            </li>
                            <li><strong>Age:</strong> <span id="profile-age"><?= $user['age'] ?? '' ?></span></li>
                            <li><strong>Gender:</strong> <span id="profile-gender"><?= $user['gender'] ?? '' ?></span>
                            </li>
                            <li><strong>Blood Group:</strong> <span
                                    id="profile-bloodGroup"><?= $user['bloodGroup'] ?? '' ?></span></li>
                            <li><strong>Email:</strong> <span id="profile-email"><?= $user['email'] ?? '' ?></span></li>
                            <li><strong>Phone:</strong> <span id="profile-phone"><?= $user['phone'] ?? '' ?></span></li>
                            <li><strong>Address:</strong> <span
                                    id="profile-address"><?= $user['address'] ?? '' ?></span></li>

                        </ul>
                        <form action="edit_patient.php" method="post">
                            <button type="submit"
                                class="mt-4 px-5 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition transform active:scale-95 shadow-lg">
                                Edit Profile
                            </button>
                        </form>
                    </div>

                </div>
                <!-- <?php if ($user): ?>
                <div class="bg-white rounded-2xl p-6 shadow">
                    <h3 class="text-2xl font-bold mb-5">Edit Profile</h3>
                    <form action="update_patient_profile.php" method="post" class="space-y-3">
                        <input class="w-full p-3 rounded-lg border" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Name" required>
                        <input class="w-full p-3 rounded-lg border" type="number" name="age" value="<?= htmlspecialchars($user['age'] ?? '') ?>" placeholder="Age" required>
                        <input class="w-full p-3 rounded-lg border" name="gender" value="<?= htmlspecialchars($user['gender'] ?? '') ?>" placeholder="Gender" required>
                        <input class="w-full p-3 rounded-lg border" name="bloodGroup" value="<?= htmlspecialchars($user['bloodGroup'] ?? '') ?>" placeholder="Blood Group" required>
                        <input class="w-full p-3 rounded-lg border" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Email" required>
                        <input class="w-full p-3 rounded-lg border" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Phone" required>
                        <textarea class="w-full p-3 rounded-lg border" name="address" placeholder="Address" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        <button class="bg-indigo-600 text-white px-5 py-3 rounded-lg font-bold" type="submit">Save Changes</button>
                    </form>
                </div>
                <?php endif; ?> -->
            </div>
        </div>

        </div>
        </div>


        <div class="bg-white rounded-3xl shadow-lg p-8 mt-10">
            <h3 class="text-2xl font-bold mb-6">Doctor Recommendations</h3>
            <?php if ($recommendations): foreach ($recommendations as $rec): ?>
                <div class="mb-4 rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
                    <div class="font-bold text-indigo-700"><?= htmlspecialchars($rec['doctor_name']) ?> · <?= htmlspecialchars($rec['specialization']) ?></div>
                    <p class="mt-2 text-gray-700"><?= nl2br(htmlspecialchars($rec['recommendation'])) ?></p>
                    <small class="text-gray-500"><?= htmlspecialchars($rec['created_at']) ?></small>
                </div>
            <?php endforeach; else: ?>
                <p class="text-gray-500">No doctor recommendations yet.</p>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-3xl shadow-lg p-8 mt-10">

            <h3 class="text-2xl font-bold mb-6">
                Appointment History
            </h3>

            <?php
            if ($appointments && $appointments->num_rows > 0) {
                ?>

                <table class="w-full border-collapse">

                    <tr class="bg-indigo-600 text-white">

                        <th class="p-3">Doctor</th>

                        <th class="p-3">Speciality</th>

                        <th class="p-3">Date</th>

                        <th class="p-3">Time</th>

                        <th class="p-3">Reason</th>

                        <th class="p-3">Action</th>

                    </tr>

                    <?php

                    while ($row = $appointments->fetch_assoc()) {

                        ?>

                        <tr class="border-b">

                            <td class="p-3"><?= htmlspecialchars($row['doctor_name']) ?></td>

                            <td class="p-3"><?= htmlspecialchars($row['speciality']) ?></td>

                            <td class="p-3"><?= htmlspecialchars($row['appointment_date']) ?></td>

                            <td class="p-3"><?= htmlspecialchars($row['appointment_time']) ?></td>

                            <td class="p-3"><?= htmlspecialchars($row['reason']) ?></td>
                            <td>
                                <a href="cancel_appointment.php?id=<?= $row['id'] ?>"
                                    onclick="return confirm('Cancel this appointment?')"
                                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">

                                    Cancel

                                </a>
                            </td>
                        </tr>
                    <?php } ?>

                </table>

                <?php
            } else {
                ?>

                <div class="text-center text-gray-500 py-8">

                    No Appointments Yet

                </div>

            <?php } ?>

        </div>
        <!-- Left Column: Documents Upload Section -->
        <div class="lg:col-span-2 space-y-10">
            <div>
                <h3 class="text-2xl font-bold mb-6 flex items-center gap-4 mt-10">
                    <i data-lucide="file-text" class="text-indigo-600 w-6 h-6 "></i> Medical Documents
                </h3>

                <!-- Upload Area -->
                <div
                    class="bg-gradient-to-br from-indigo-50 to-purple-50 border-2 border-dashed border-indigo-300 rounded-3xl p-8 mb-8 hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                    <div class="text-center">
                        <i data-lucide="upload-cloud" class="w-12 h-12 text-indigo-600 mx-auto mb-4"></i>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">Upload Medical Documents</h4>
                        <p class="text-gray-600 mb-6 text-sm">Upload your PDF documents (prescriptions, reports, etc.)
                        </p>

                        <form id="uploadForm" enctype="multipart/form-data" class="space-y-4">
                            <div class="relative">
                                <input type="file" id="pdfFile" name="pdfFile" accept="application/pdf" multiple
                                    class="hidden">
                                <label for="pdfFile"
                                    class="inline-block cursor-pointer px-8 py-3 bg-indigo-600 text-white rounded-2xl font-semibold hover:bg-indigo-700 transition-all transform hover:scale-105 active:scale-95 shadow-lg">
                                    <i data-lucide="file-up" class="inline w-4 h-4 mr-2"></i>Choose Files
                                </label>
                            </div>
                            <div id="fileName" class="text-sm text-gray-600"></div>
                        </form>
                    </div>

                    <div id="uploadedFilesList" class="space-y-4">
                        <div class="text-center py-8 text-gray-500">
                            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 text-gray-300"></i>
                            <p>No documents uploaded yet</p>
                        </div>
                    </div>




                    <!-- <div class="lg:col-span-2 space-y-10">
                    <div>
                        <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                            <i data-lucide="activity" class="text-indigo-600 w-5 h-5"></i> Health Statistics
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-blue-50 p-6 rounded-3xl border border-blue-100 depth-shadow">
                                <p class="text-sm font-bold text-blue-800 mb-1">Blood Pressure</p>
                                <p class="text-2xl font-black text-blue-900">120/80</p>
                            </div>
                            <div class="bg-rose-50 p-6 rounded-3xl border border-rose-100 depth-shadow">
                                <p class="text-sm font-bold text-rose-800 mb-1">Heart Rate</p>
                                <p class="text-2xl font-black text-rose-900">72 bpm</p>
                            </div>
                        </div>
                    </div> -->
                    <!-- <div>
                        <h3 class="text-xl font-bold mb-4">Appointments</h3>
                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200 flex justify-between items-center">
                            <div>
                                <p class="font-bold">Neurology Consultation</p>
                                <p class="text-sm text-gray-500">Dr. Sarah Chen • Tomorrow 10:30 AM</p>
                            </div>
                            <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold">Join Call</button>
                        </div>
                    </div>
                </div>
                <aside class="space-y-6">
                    <div class="bg-indigo-50 p-6 rounded-3xl border border-indigo-100">
                        <h4 class="font-bold mb-2">Member Since</h4>
                        <p class="text-indigo-600 font-bold">January 2024</p>
                    </div> -->
                    <!-- <button onclick="handleLogout()" class="py-4 text-red-300 font-bold border-2 border-red-100 rounded-2xl hover:bg-red-50 transition">Logout</button>
                </aside> -->
                </div>
            </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-gray-900 text-gray-400 py-12 px-6 mt-20">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <img src="logo.jpeg" class="w-10 h-10" alt="MedEase Logo">
                    <span class="text-xl font-bold text-white">MedEase</span>
                </div>
                <p class="max-w-xs">Connecting patients with the best medical professionals worldwide.</p>
            </div>
            <div>
                <h4 class="text-white font-bold mb-4">Quick Links</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="https://esummit.bitsindri.ac.in/">Find Doctors</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-bold mb-4">Contact</h4>
                <i data-lucide="instagram" class="w-4 h-4 cursor-pointer hover:scale-110 transition"></i>
                <i data-lucide="twitter" class="w-4 h-4 cursor-pointer hover:scale-110 transition"></i>
                <i data-lucide="facebook" class="w-4 h-4 cursor-pointer hover:scale-110 transition"></i>
                <p class="text-sm">support@MedEase.com</p>
            </div>
        </div>
    </footer>

    <script>
        window.REGISTERED_DOCTORS = <?= json_encode($registered_doctors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="script.js"></script>
</body>

</html>