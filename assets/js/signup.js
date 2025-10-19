document.addEventListener("DOMContentLoaded", () => {

    const signupForm = document.getElementById("signupForm");

    signupForm.addEventListener("submit", async (e) => {
        e.preventDefault();
    

    // Get Form Data and store it in data object
    const data = {
        fname: document.getElementById("signup-fname").value.trim(),
        lname: document.getElementById("signup-lname").value.trim(),
        email: document.getElementById("signup-email").value.trim(),
        password: document.getElementById("signup-password").value,
        confPass: document.getElementById("confirm-password").value
    }

    // Check if password match
    if (data.password !== data.confPass) {
        Swal.fire({
            icon: "error",
            title:  "Password Mismatch",
            text: "Your passwords do not match. Please try again.",
        });
        return;
    }

    try {
            // Sending data to database
        const response = await fetch("/backend/signup.php", {
            method: "POST",
            body: JSON.stringify({
                action: "register",
                ...data
            }),
            headers: {
                "Content-Type": "application/josn"
            }
        });

        const fetch_data = await response.json();

        if (!response.ok) {
            Swal.fire({
                icon: "error",
                title: "title",
                text: data.message || "Something went wrong. Try again.",
            })
            return
        }

        Swal.fire({
            icon: "success",
            title: "Account Created!",
            text: data.message || "You can now log in",
            showConfirmButton: false,
            timer: 1500,
        }).then(() => {
            window.location.href = "/";
        });
    } catch (error) {
        Swal.fire({
            icon: "error",
            title: "Network Error",
            text: "Unable to connect to the server. Please try again later.",
        });
        console.log(error);
    }
  });
})