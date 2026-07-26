// AUTH STATE
let isLoggedIn = false;
let currentUser = null;
// Profile storage object (initialized to avoid ReferenceError during signup)
let myProfile = {};

// OPEN/CLOSE MODAL
function toggleAuthModal() {
  const modal = document.querySelector("#auth-modal");
  modal.classList.toggle("hidden");
}

// SWITCH LOGIN / SIGNUP
function switchAuthMode(mode) {
  if (!mode) return;
  if (mode === "register") mode = "signup";

  const loginForm = document.querySelector("#login-form");
  const signupForm = document.querySelector("#signup-form");

  if (loginForm && signupForm) {
    loginForm.classList.toggle("hidden", mode === "signup");
    signupForm.classList.toggle("hidden", mode === "login");
  }

  if (mode === "signup") switchRegisterType("patient");
  if (mode === "login") switchLoginType("patient");
}

function switchLoginType(type) {
  const patientForm = document.getElementById("patient-login-form");
  const doctorForm = document.getElementById("doctor-login-form");
  const patientTab = document.getElementById("patient-login-tab");
  const doctorTab = document.getElementById("doctor-login-tab");

  if (!patientForm || !doctorForm) return;

  const isPatient = type === "patient";
  patientForm.classList.toggle("hidden", !isPatient);
  doctorForm.classList.toggle("hidden", isPatient);
  patientTab.classList.toggle("active", isPatient);
  doctorTab.classList.toggle("active", !isPatient);
}

function switchRegisterType(type) {
  const patientForm = document.getElementById("patient-signup-form");
  const doctorForm = document.getElementById("doctor-signup-form");
  const patientTab = document.getElementById("patient-register-tab");
  const doctorTab = document.getElementById("doctor-register-tab");

  if (!patientForm || !doctorForm) return;

  const isPatient = type === "patient";
  patientForm.classList.toggle("hidden", !isPatient);
  doctorForm.classList.toggle("hidden", isPatient);
  patientTab.classList.toggle("active", isPatient);
  doctorTab.classList.toggle("active", !isPatient);
}

// LOGIN / REGISTER
function handleAuthAction(type) {
  // Login or Signup
  if (type === "signup") {
    const form = document.getElementById("signup-form");
    if (!form) return alert("Signup form not found");
    const inputs = form.querySelectorAll("input, select, textarea");
    const user = {};
    inputs.forEach((inp) => {
      if (!inp.name) return;
      if (inp.type === "radio") {
        if (inp.checked) user[inp.name] = inp.value;
        return;
      }
      user[inp.name] = inp.value;
    });

    // Merge with existing profile defaults
    myProfile = Object.assign({}, myProfile, user);
    localStorage.setItem("medease_user", JSON.stringify(myProfile));
    localStorage.setItem("loggedIn", "true");
    isLoggedIn = true;
    currentUser = { name: myProfile.name || myProfile.name || "User" };
    toggleAuthModal();
    populateProfileUI();
    updateNavbar();
    navigateTo("profile");
    // alert('Registered successfully!');
    return;
  }

  if (type === "login") {
    const form = document.getElementById("login-form");
    if (!form) return alert("Login form not found");
    const emailInput = form.querySelector('input[name="email"]');
    if (!emailInput) return alert("Login email field missing");
    const email = emailInput.value.trim();

    const stored = localStorage.getItem("medease_user");
    if (stored) {
      const storedUser = JSON.parse(stored);
      if (storedUser.email && storedUser.email === email) {
        localStorage.setItem("loggedIn", "true");
        isLoggedIn = true;
        currentUser = { name: storedUser.name || "User" };
        toggleAuthModal();
        populateProfileUI();
        updateNavbar();
        navigateTo("profile");
        // alert('Logged in successfully!');
        return;
      }
    }

    alert("Invalid login credentials");
    return;
  }
}

// LOGOUT
function handleLogout() {
  isLoggedIn = false;
  currentUser = null;

  navigateTo("home");
}

// --- DOCUMENT UPLOAD FUNCTIONALITY ---
let uploadedDocuments = [];

// Load documents from localStorage on page load
function loadUploadedDocuments() {
  const saved = localStorage.getItem("medease_documents");
  uploadedDocuments = saved ? JSON.parse(saved) : [];
  renderUploadedDocuments();
}

// Handle file upload
function handleFileUpload(files) {
  Array.from(files).forEach((file) => {
    if (file.type !== "application/pdf") {
      alert("Please upload PDF files only");
      return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
      const document = {
        id: Date.now() + Math.random(),
        name: file.name,
        size: (file.size / 1024).toFixed(2),
        uploadDate: new Date().toLocaleDateString(),
        data: event.target.result,
      };

      uploadedDocuments.push(document);
      localStorage.setItem(
        "medease_documents",
        JSON.stringify(uploadedDocuments),
      );
      renderUploadedDocuments();

      // Clear input
      document.getElementById("pdfFile").value = "";
      document.getElementById("fileName").textContent = "";
      alert("Document uploaded successfully!");
    };
    reader.readAsDataURL(file);
  });
}

// Render uploaded documents
function renderUploadedDocuments() {
  const container = document.getElementById("uploadedFilesList");

  if (uploadedDocuments.length === 0) {
    container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-2 text-gray-300"></i>
                <p>No documents uploaded yet</p>
            </div>
        `;
    lucide.createIcons();
    return;
  }

  container.innerHTML = uploadedDocuments
    .map(
      (doc) => `
        <div class="flex items-center justify-between bg-gray-50 p-4 rounded-2xl border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all">
            <div class="flex items-center gap-4 flex-1">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="file-pdf" class="text-red-600 w-6 h-6"></i>
                </div>
                <div class="flex-1">
                    <h5 class="font-semibold text-gray-800">${doc.name}</h5>
                    <p class="text-xs text-gray-500">${doc.size} KB • ${doc.uploadDate}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="viewDocument(${doc.id})" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold text-sm hover:bg-indigo-700 transition flex items-center gap-2">
                    <i data-lucide="eye" class="w-4 h-4"></i> View
                </button>
                <button onclick="deleteDocument(${doc.id})" class="px-4 py-2 bg-red-100 text-red-600 rounded-lg font-semibold text-sm hover:bg-red-200 transition flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Delete
                </button>
            </div>
        </div>
    `,
    )
    .join("");

  lucide.createIcons();
}

// View document
function viewDocument(docId) {
  const doc = uploadedDocuments.find((d) => d.id === docId);
  if (!doc) return;

  // Open PDF in new tab
  const newWindow = window.open();
  newWindow.document.write(`
        <html>
        <head>
            <title>${doc.name}</title>
            <style>
                body { margin: 0; }
                iframe { width: 100%; height: 100vh; border: none; }
            </style>
        </head>
        <body>
            <iframe src="${doc.data}"></iframe>
        </body>
        </html>
    `);
}

// Delete document
function deleteDocument(docId) {
  if (confirm("Are you sure you want to delete this document?")) {
    uploadedDocuments = uploadedDocuments.filter((d) => d.id !== docId);
    localStorage.setItem(
      "medease_documents",
      JSON.stringify(uploadedDocuments),
    );
    renderUploadedDocuments();
    alert("Document deleted successfully!");
  }
}

// --- DATA ---
const STATIC_DOCTORS = [];

// Registered doctors come from the PHP/MySQL database. New registrations
// automatically become searchable cards without editing this JavaScript file.
const DOCTORS = [...(window.REGISTERED_DOCTORS || []), ...STATIC_DOCTORS];

const CATEGORIES = [
  "Neurologist",
  "Cardiologist",
  "Orthopedic",
  "Pediatrician",
  "Dermatologist",
  "Cardiologist",
  "Dermatologist",
  "Endocrinologist",
  "Gastroenterologist",
  "Geriatrician",
  "Hematologist",
  "Nephrologist",
  "Neurologist",
  "Oncologist",
  "Ophthalmologist",
  "Orthopedic Surgeon",
  "Otolaryngologist",
  "Pediatrician",
  "Psychiatrist",
  "Pulmonologist",
  "Radiologist",
  "Rheumatologist",
  "Urologist",
  "Anesthesiologist",
  "Pathologist",
  "Obstetrician/Gynecologist",
  "General Surgeon",
  "Allergist/Immunologist",
  "Infectious Disease Specialist",
  "Family Medicine Physician",
  "Internal Medicine Physician",
  "Emergency Medicine Physician",
];

let activeCategory = "All";
let activeSearchQuery = "";

function normalizeText(value) {
  return String(value || "")
    .toLowerCase()
    .trim();
}

function getFilteredDoctors() {
  const query = normalizeText(activeSearchQuery);
  return DOCTORS.filter((doc) => {
    const matchesCategory =
      activeCategory === "All" || doc.type === activeCategory;
    const matchesQuery =
      !query ||
      [doc.name, doc.type, doc.workplace].some((value) =>
        normalizeText(value).includes(query),
      );
    return matchesCategory && matchesQuery;
  });
}

// --- NAVIGATION LOGIC ---
function navigateTo(pageId) {
  // Hide all pages
  document
    .querySelectorAll(".page-content")
    .forEach((p) => p.classList.remove("page-active"));
  // Show target page
  document.getElementById(`page-${pageId}`).classList.add("page-active");

  // Update nav styles
  document
    .querySelectorAll(".nav-link")
    .forEach((l) =>
      l.classList.remove("text-indigo-600", "border-b-2", "border-indigo-600"),
    );
  const activeNav = document.getElementById(`nav-${pageId}`);
  if (activeNav)
    activeNav.classList.add(
      "text-indigo-600",
      "border-b-2",
      "border-indigo-600",
    );

  // Clear home search input when navigating away from Home
  if (pageId !== "home") {
    const homeSearch = document.getElementById("home-search-input");
    if (homeSearch) homeSearch.value = "";
  }

  // Reset search when navigating to doctors page
  if (pageId === "doctors") {
    activeCategory = "All";
    activeSearchQuery = "";
    const doctorSearchInput = document.getElementById("doctor-search-input");
    if (doctorSearchInput) {
      doctorSearchInput.value = "";
    }
    filterDoctors("All", "");
  }

  window.scrollTo(0, 0);
}

if (window.lucide) {
  lucide.createIcons();
}

// --- RENDER DOCTORS ---
function renderDoctorCards(list, containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;

  if (!list.length) {
    container.innerHTML = `
                    <div class="col-span-full rounded-[2rem] border border-dashed border-gray-300 bg-white/70 p-10 text-center text-gray-500">
                        <h3 class="text-xl font-semibold text-gray-700">No doctors found</h3>
                        <p class="mt-2">Try a different specialty or search term.</p>
                    </div>
                `;
    lucide.createIcons();
    return;
  }

  container.innerHTML = list
    .map(
      (doc) => `
                <div class="glass-card p-6 rounded-[2rem] group">
                    <div class="flex gap-4 mb-4">
                        <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                            ${doc.img}
                        </div>
                        <div>
                            <h4 class="font-bold text-lg">${doc.name}</h4>
                            <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-md">${doc.type}</span>
                        </div>
                    </div>
                    <div class="space-y-2 mb-6 text-sm text-gray-500">
                        <div class="flex items-center gap-2"><i data-lucide="star" class="w-3 h-3 text-yellow-400 fill-current"></i> ${doc.rating} Rating</div>
                        <div class="flex items-center gap-2"><i data-lucide="map-pin" class="w-3 h-3 text-indigo-400"></i> ${doc.workplace}</div>
                    </div>
                    <button onclick='openDoctorProfile(${JSON.stringify(doc.id)})'  class="w-full py-3 bg-gray-50 text-indigo-600 rounded-xl font-bold group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-sm">
                            View Profile
                        </button>
                </div>
            `,
    )
    .join("");
  lucide.createIcons(); // Re-initialize icons for new content
}

// --- FILTERING LOGIC ---
function filterDoctors(category, query = activeSearchQuery) {
  activeCategory = category;
  activeSearchQuery = query;
  const filtered = getFilteredDoctors();
  renderDoctorCards(filtered, "doctors-main-grid");
  document.getElementById("result-count").innerText =
    `${filtered.length} results found`;

  // Update button styles
  document.querySelectorAll(".category-btn").forEach((btn) => {
    btn.classList.remove(
      "bg-indigo-600",
      "text-white",
      "shadow-lg",
      "translate-x-2",
    );
    if (
      btn.innerText === category ||
      (category === "All" && btn.innerText === "All Specialities")
    ) {
      btn.classList.add(
        "bg-indigo-600",
        "text-white",
        "shadow-lg",
        "translate-x-2",
      );
    }
  });
}

function searchDoctors(query) {
  activeSearchQuery = query.trim();
  filterDoctors(activeCategory, activeSearchQuery);
}

function searchFromHome() {
  const searchInput = document.getElementById("home-search-input");
  const query = searchInput ? searchInput.value : "";
  activeSearchQuery = query.trim();
  navigateTo("doctors");
  filterDoctors(activeCategory, activeSearchQuery);

  const doctorSearchInput = document.getElementById("doctor-search-input");
  if (doctorSearchInput) {
    doctorSearchInput.value = activeSearchQuery;
  }
}

// --- INITIALIZATION ---
window.onload = () => {
  lucide.createIcons();
  updateNavbar();
  populateProfileUI();
  loadUploadedDocuments();
  if (window.location.search.includes("profile=1")) {
    navigateTo("profile");
  }

  // Home page doctors
  renderDoctorCards(DOCTORS.slice(0, 4), "famous-doctors-grid");

  // Doctors page initial load
  filterDoctors("All", "");

  // Setup categories sidebar
  const catList = document.getElementById("category-list");
  CATEGORIES.forEach((cat) => {
    const btn = document.createElement("button");
    btn.className =
      "category-btn w-full text-left px-4 py-3 rounded-xl transition-all hover:bg-indigo-600 hover:text-white text-grey-700";
    btn.innerText = cat;
    btn.onclick = () => filterDoctors(cat, activeSearchQuery);
    catList.appendChild(btn);
  });

  const homeSearchInput = document.getElementById("home-search-input");
  if (homeSearchInput) {
    homeSearchInput.addEventListener("keydown", (event) => {
      if (event.key === "Enter") {
        event.preventDefault();
        searchFromHome();
      }
    });
  }

  const doctorSearchInput = document.getElementById("doctor-search-input");
  if (doctorSearchInput) {
    doctorSearchInput.addEventListener("input", (event) => {
      searchDoctors(event.target.value);
    });
  }

  // File upload handler
  const pdfFileInput = document.getElementById("pdfFile");
  if (pdfFileInput) {
    pdfFileInput.addEventListener("change", (event) => {
      const files = event.target.files;
      if (files.length > 0) {
        const fileNames = Array.from(files)
          .map((f) => f.name)
          .join(", ");
        document.getElementById("fileName").textContent =
          `Selected: ${fileNames}`;
        handleFileUpload(files);
      }
    });
  }

  // Wire auth forms if present
  const signupForm = document.querySelector("signup-form");

  const loginForm = document.querySelector("login-form");
};

// Populate profile UI from stored user data
function populateProfileUI() {
  const stored = localStorage.getItem("medease_user");
  if (!stored) return;
  try {
    const data = JSON.parse(stored);
    myProfile = Object.assign({}, myProfile, data);
    Object.keys(myProfile).forEach((key) => {
      const el = document.getElementById(`profile-${key}`);
      if (el) el.textContent = myProfile[key];
    });
    // also update brief/alternate name and ID displays
    const brief = document.getElementById("profile-name-brief");
    if (brief) brief.textContent = myProfile.name || "";
    const idEl = document.getElementById("profile-ID");
    if (idEl) idEl.textContent = myProfile.ID || myProfile.id || "";
  } catch (err) {
    console.error("Failed to populate profile UI", err);
  }
}

function updateNavbar() {
  const authSection = document.getElementById("auth-section");

  if (localStorage.getItem("loggedIn")) {
    const user = JSON.parse(localStorage.getItem("medease_user"));

    authSection.innerHTML = `

<span class="font-semibold text-gray-700">
${user.name}
</span>

<button onclick="logout()" 
class="px-3 py-1 text-sm border border-red-400 text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition">
Logout
</button>

`;
  }
}

function logout() {
  localStorage.removeItem("loggedIn");
  location.reload();
}

// Open doctor's profile page and populate details
function openDoctorProfile(id) {
  const doc = DOCTORS.find((d) => d.id === id);
  if (!doc) {
    alert("Doctor not found");
    return;
  }

  const container = document.getElementById("doctor-profile-container");
  if (!container) return;

  container.innerHTML = `
        <div class="bg-indigo-100 rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden">
            <div class="h-51 bg-gradient-to-r from-indigo-600 to-purple-700 relative">
                <div class=" bottom-0 left-12 flex items-end gap-6" style="z-index:10;">
                    <div class="w-40 h-40 rounded-1xl bg-white p-2 shadow-2xl border border-gray-100">
                        <div class="w-full h-full bg-gray-100 rounded-[1.25rem] flex items-center justify-center text-6xl">${doc.img}</div>
                    </div>
                    <div class="mb-4">
                        <h2 class="text-3xl font-bold text-white">${doc.name}</h2>
                        <p class="text-white font-medium">${doc.type} • ${doc.exp} yrs exp</p>
                        <p class="text-white font-medium">${doc.workplace}</p>
                    </div>
                </div>
            </div>

            <div class="mt-28 p-12">
                <div class="space-y-4 text-gray-700">
                    <p><strong>Rating:</strong> ${doc.rating}</p>
                    <p><strong>Experience:</strong> ${doc.exp} years</p>
                    <p><strong>Workplace:</strong> ${doc.workplace}</p>
                   <p><strong>Time:</strong> ${doc.time}</p>
                    <p><strong>Specialty:</strong> ${doc.type}</p>
                    <p><strong>E-mail:</strong> ${doc.email}</p>
                    <p><strong>Phone:</strong> ${doc.phone}</p>
                    ${doc.registrationNo ? `<p><strong>Medical Registration No.:</strong> ${doc.registrationNo}</p>` : ""}
                    ${doc.address ? `<p><strong>Address:</strong> ${doc.address}</p>` : ""}
                    ${doc.gender ? `<p><strong>Gender:</strong> ${doc.gender}</p>` : ""}
                    <p><strong>About:</strong> Experienced ${doc.type} providing compassionate care and personalized treatment plans.</p>
                </div>
               

<form action="book_appointment.php"  class ="flex flex-col items-center"method="POST">

<input type="hidden" name="doctor" value="${doc.name}">
<input type="hidden" name="speciality" value="${doc.type}">

<input type="date"
name="date"
required
class="w-[60%] border rounded-xl  p-3 mt-3">

<input type="time"
name="time"
required
class="w-[60%]  border rounded-xl p-3 mt-3">

<textarea
name="reason"
placeholder="Reason for Appointment"
required
class=" w-[60%]  border rounded-xl p-3 mt-3"></textarea>

<div class="flex gap-4 mt-4">

<button
type="button"
onclick="navigateTo('doctors')"
class="px-6 py-3 bg-gray-100 rounded-xl">
Back to list
</button>

<button
type="submit"
class="px-6 py-3 bg-indigo-600 text-white rounded-xl">
Book Appointment
</button>

</div>

</form>

</div>
                </div>
            </div>
        </div>`;

  if (window.lucide) lucide.createIcons();
  navigateTo("doctor");
}

// let myProfile ={
//     ID:"12",
//     name: "Prashant Rana",
//     email: "prashant.rana@example.com",
//     gender : "Male",
//     bloodGroup: "O+",
//     weight: "70kg",
//     height: "5'9\"",
//     dob: "1990-01-01",
//     phone: "+91 98765 43210",
//     address: "123 Main St, City, Country",
//     nameOfDoctor: "Dr. Anna Rana",
//     doctorEmail: "anna.rana@example.com",
//     doctorPhone: "+91 98765 43210",
//     doctorWorkplace: "City Hospital",
// }

const uploadForm = document.getElementById("uploadForm");
if (uploadForm) {
  uploadForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fileInput = document.getElementById("pdfFile");
    if (!fileInput || fileInput.files.length === 0) {
      return alert("Please select a PDF file first.");
    }
    const formData = new FormData();
    formData.append("pdfFile", fileInput.files[0]);
    try {
      const response = await fetch("/upload", {
        method: "POST",
        body: formData,
      });
      alert(await response.text());
    } catch (error) {
      console.error(error);
      alert("Upload failed.");
    }
  });
}
