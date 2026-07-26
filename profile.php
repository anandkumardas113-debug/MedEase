<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location:index.html");
    exit();
}

include "config.php";

$id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
?>

 <!-- PROFILE PAGE -->
    <main id="page-profile" class="page-content max-w-5xl mx-auto py-10 px-6">
        <div class="bg-indigo-100 rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden">
            <div class="h-51 bg-gradient-to-r from-indigo-600 to-purple-700 relative">
                <div class="bottom-0 left-12 flex items-end gap-6" style="z-index: 10;">
                    <div class="w-32 h-32 rounded-3xl bg-white p-1 shadow-2xl border border-gray-100">
                        <div class="w-full h-full bg-gray-100 rounded-[1.25rem] flex items-center justify-center text-5xl">👤</div>
                    </div>
                    <div class="mb-4">
                        <h2 class="text-3xl font-bold text-white"><span id="profile-name">Prashant Rana</span></h2>
                        <p class="text-white font-medium">Patient ID: <span id="profile-ID">12</span></p>
                    </div>
                </div>
            </div>

            <div class="mt-24 p-12 grid grid-cols-1 lg:grid-cols-2 gap-12">

                <div class="space-y-6">
                    <div>
                        <h3 class="text-2xl font-bold mb-6 flex items-center gap-2">
                            <i data-lucide="user" class="text-indigo-600 w-6 h-6"></i> Personal Information
                        </h3>
                        <ul class="space-y-4 text-gray-700">
                            <li><strong>Name:</strong> <span id="profile-name-brief">Prashant Rana</span></li>
                            <li><strong>Age:</strong> <span id="profile-age">30</span></li>
                            <li><strong>Gender:</strong> <span id="profile-gender">Male</span></li>
                            <li><strong>Blood Group:</strong> <span id="profile-bloodGroup">O+</span></li>
                            <li><strong>Email:</strong> <span id="profile-email">prashant.rana@example.com</span></li>
                            <li><strong>Phone:</strong> <span id="profile-phone">+91 9876543210</span></li>
                            <li><strong>Address:</strong> <span id="profile-address">123 Main St, City, State</span></li>

                        </ul>
                    </div>

                </div>

