document.addEventListener("DOMContentLoaded", () => {
  const expandBtn = document.querySelector(".expand-btn");
  if (!expandBtn) return;

  expandBtn.addEventListener("click", openAddPetModal);
});

// Function to create and show the modal
function openAddPetModal() {
  // Prevent multiple modals
  if (document.getElementById("addPetModal")) return;

  const modal = document.createElement("div");
  modal.id = "addPetModal";
  modal.className = "modal-overlay";
  modal.innerHTML = `
    <div class="modal-content">
      <span class="close-btn" onclick="closeAddPetModal()">&times;</span>
      <h2>Add a New Pet</h2>
      <form id="addPetForm">
        <label>Pet Microchip Number</label>
        <input type="text" name="PetChipNum" required>

        <label>Pet Name</label>
        <input type="text" name="PetName" required>

        <label>Species</label>
        <input type="text" name="Species" required>

        <label>Breed</label>
        <input type="text" name="Breed">

        <label>Gender</label>
        <select name="Gender" required>
          <option value="">Select Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>

        <label>Age</label>
        <input type="number" name="Age" min="0">

        <label>Weight (kg)</label>
        <input type="number" step="0.1" name="Weight" min="0">

        <label>Color/Markings</label>
        <input type="text" name="ColorMarkings">

        <label>Pet Image</label>
        <input type="file" name="PetPic" accept="image/*">

        <button type="submit" class="submit-btn">Add Pet</button>
      </form>
    </div>
  `;
  document.body.appendChild(modal);

  const form = document.getElementById("addPetForm");
  form.addEventListener("submit", async e => {
    e.preventDefault();

    const formData = new FormData(form);

    try {
      const response = await fetch("/backend/add-pet-api.php", {
        method: "POST",
        body: formData
      });

      // Check if the response is JSON
      const contentType = response.headers.get("content-type");
      if (!contentType || !contentType.includes("application/json")) {
        throw new Error("Server did not return JSON.");
      }

      const data = await response.json();

      if (data.status === "success") {
        Swal.fire({
          icon: "success",
          title: "Success",
          text: data.message,
          timer: 2000,
          showConfirmButton: false
        });

        closeAddPetModal();

        // Optional: dynamically add the new pet card to the pets list
        appendNewPetCard(data.PetID, data.PetName, data.PetPic);
      } else {
        Swal.fire("Error", data.message, "error");
      }
    } catch (err) {
      console.error("Fetch Error:", err);
      Swal.fire("Error", "Failed to add pet.", "error");
    }
  });
}

// Function to close modal
function closeAddPetModal() {
  const modal = document.getElementById("addPetModal");
  if (modal) modal.remove();
}

// Optional helper to append new pet card to pets section dynamically
function appendNewPetCard(petID, petName, petPic) {
  const petsContainer = document.querySelector(".pets-container");
  if (!petsContainer) return;

  const card = document.createElement("a");
  card.href = `pets.php?pet_id=${encodeURIComponent(petID)}`;
  card.className = "pet-card clickable";

  const img = document.createElement("img");
  img.src = petPic ? `/storage/images/pets/${petPic}` : "/assets/images/petsamples.png";
  img.alt = petName;
  img.className = "pet-image";

  card.appendChild(img);
  petsContainer.appendChild(card);
}
