// STUDENT REGISTRATION FORM

const studentRegistrationForm = document.getElementById("studentRegistrationForm");

if (studentRegistrationForm) {
  studentRegistrationForm.addEventListener("submit", function (event) {
    console.log("Student registration submit clicked");
    
    const fullName = document.getElementById("fullName").value;
    const studentNumber = document.getElementById("studentNumber").value;
    const email = document.getElementById("email").value;
    const phoneNumber = document.getElementById("phoneNumber").value;
    const city = document.getElementById("city").value;
    const province = document.getElementById("province").value;
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const gender = document.getElementById("gender").value;

    console.log({ fullName, studentNumber, email, phoneNumber, city, province, password, confirmPassword, gender });

    const digitsOnly = /^\d+$/;
    if (!digitsOnly.test(studentNumber)) {
      alert("Student Number must contain only digits.");
      event.preventDefault();
      return;
    }
    if (!digitsOnly.test(phoneNumber)) {
      alert("Phone Number must contain only digits.");
      event.preventDefault();
      return;
    }
    if (!email.includes("@")) {
      alert("Email must contain an @ symbol.");
      event.preventDefault();
      return;
    }
    if (password !== confirmPassword) {
      alert("Passwords do not match.");
      event.preventDefault();
      return;
    }

    console.log("Student registration validation passed, submitting form...");
    // Form will submit to PHP to save in DB
  });
}


// STUDENT LOGIN FORM

const studentLoginForm = document.querySelector("form[action='student_login.php']");

if (studentLoginForm) {
  studentLoginForm.addEventListener("submit", function (event) {
    console.log("Student login submit clicked");

    const studentNumber = document.getElementById("studentNumber").value;
    const password = document.getElementById("password").value;

    console.log({ studentNumber, password });
    // Form will submit to PHP
  });
}


// ADMIN REGISTRATION FORM

const adminRegistrationForm = document.getElementById("adminRegistrationForm");

if (adminRegistrationForm) {
  adminRegistrationForm.addEventListener("submit", function (event) {
    console.log("Admin registration submit clicked");

    const fullName = document.getElementById("fullName").value;
    const adminID = document.getElementById("adminID").value;
    const email = document.getElementById("email").value;
    const phoneNumber = document.getElementById("phoneNumber").value;
    const department = document.getElementById("department").value;
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;
    const gender = document.getElementById("gender").value;

    console.log({ fullName, adminID, email, phoneNumber, department, password, confirmPassword, gender });

    const digitsOnly = /^\d+$/;
    if (!digitsOnly.test(adminID)) {
      alert("Admin ID must contain only digits.");
      event.preventDefault();
      return;
    }
    if (!digitsOnly.test(phoneNumber)) {
      alert("Phone Number must contain only digits.");
      event.preventDefault();
      return;
    }
    if (!email.includes("@")) {
      alert("Email must contain an @ symbol.");
      event.preventDefault();
      return;
    }
    if (password !== confirmPassword) {
      alert("Passwords do not match.");
      event.preventDefault();
      return;
    }

    console.log("Admin registration validation passed, submitting form...");
  });
}

// ADMIN LOGIN FORM

const adminLoginForm = document.querySelector("form[action='admin_login.php']");

if (adminLoginForm) {
  adminLoginForm.addEventListener("submit", function (event) {
    console.log("Admin login submit clicked");

    const adminID = document.getElementById("adminID").value;
    const password = document.getElementById("password").value;

    console.log({ adminID, password });
    // Form will submit to PHP
  });
}
