document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("searchPetInput");
  const searchBtn = document.getElementById("searchPetBtn");
  const petDetailsGrid = document.getElementById("petDetailsGrid");
  const petImage = document.getElementById("petImage");
  const petMicrochip = document.getElementById("petMicrochip");
  const petDetailsDiv = document.getElementById("petDetails");
  const petRecordsDiv = document.getElementById("petRecords");

  // Reports buttons from static HTML
  const updateVaccBtn = document.querySelector(".vet-actions-list button:nth-child(1)");
  const generateReportBtn = document.querySelector(".vet-actions-list button:nth-child(2)");
  const writeNoteBtn = document.querySelector(".vet-actions-list button:nth-child(3)");

  if (!searchInput || !searchBtn || !petDetailsGrid || !petImage || !petMicrochip || !petDetailsDiv || !petRecordsDiv) {
    console.error("One or more pet detail elements are missing in HTML!");
    return;
  }

  let currentPet = null;

  const searchPet = async () => {
    const query = searchInput.value.trim();
    if (!query) return alert("Please enter Microchip Number.");

    try {
      const response = await fetch("/backend/fetch-pet-api.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `PetChipNum=${encodeURIComponent(query)}`
      });

      if (!response.ok) return alert(`Server returned status ${response.status}`);

      const data = await response.json();
      if (data.status !== "success") return alert(data.message);

      const pet = data.details;
      currentPet = pet;

      // Populate grid
      petDetailsGrid.style.display = "grid";
      petImage.src = pet.PetPic ? `/storage/images/pets/${pet.PetPic}` : "../storage/images/pets/pet1.png";
      petMicrochip.textContent = pet.PetChipNum;

      petDetailsDiv.innerHTML = `
        <p><strong>Name:</strong> ${pet.PetName}</p>
        <p><strong>Species:</strong> ${pet.Species}</p>
        <p><strong>Breed:</strong> ${pet.Breed}</p>
        <p><strong>Gender:</strong> ${pet.Gender}</p>
        <p><strong>Age:</strong> ${pet.Age}</p>
        <p><strong>Weight:</strong> ${pet.Weight} kg</p>
        <p><strong>Color/Markings:</strong> ${pet.ColorMarkings}</p>
      `;

      // ✅ Pet Records buttons
      petRecordsDiv.innerHTML = `
        <button class="vet-action-btn" onclick="openPopout('records-vaccination', '${pet.PetID}', '${pet.PetName}')">Vaccination History</button>
        <button class="vet-action-btn" onclick="openPopout('records-medical', '${pet.PetID}', '${pet.PetName}')">Medical Records</button>
        <button class="vet-action-btn" onclick="openPopout('records-notes', '${pet.PetID}', '${pet.PetName}')">Notes</button>
      `;
    } catch (error) {
      console.error("Fetch Error:", error);
      alert("Error fetching pet details.");
    }
  };

  searchBtn.addEventListener("click", searchPet);
  searchInput.addEventListener("keypress", e => e.key === "Enter" && searchPet());

  // Reports buttons popouts
  if (updateVaccBtn) updateVaccBtn.addEventListener("click", () => openPopout('reports-update-vaccinations', currentPet?.PetID, currentPet?.PetName));
  if (generateReportBtn) generateReportBtn.addEventListener("click", () => openPopout('reports-generate-reports', currentPet?.PetID, currentPet?.PetName));
  if (writeNoteBtn) writeNoteBtn.addEventListener("click", () => openPopout('reports-write-note', currentPet?.PetID, currentPet?.PetName));
});

// Generic popout function
function openPopout(type, petID, petName) {
  if (!petID || !petName) return alert("Please search a pet first.");

  const popoutID = `${type}-${petID}`;
  let popout = document.getElementById(popoutID);

  if (!popout) {
    popout = document.createElement("div");
    popout.id = popoutID;
    popout.className = "popout-modal";
    popout.innerHTML = `
      <div class="popout-content">
        <span class="close-btn" onclick="togglePopout('${popoutID}')">&times;</span>
        <h4>${formatType(type)} for ${petName}</h4>
        <div class="popout-body">Loading...</div>
      </div>
    `;
    document.body.appendChild(popout);
  }

  popout.style.display = "block";
}

function togglePopout(popoutID) {
  const popout = document.getElementById(popoutID);
  if (!popout) return;
  popout.style.display = popout.style.display === "block" ? "none" : "block";
}

function formatType(type) {
  return type.replace(/-/g, " ").replace(/\b\w/g, l => l.toUpperCase());
}
