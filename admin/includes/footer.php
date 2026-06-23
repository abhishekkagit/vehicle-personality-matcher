    </div> <!-- content-area -->
</div> <!-- main-area -->
</div> <!-- admin-wrapper -->

<script>
function openAddVehicleModal() {
    document.getElementById("addVehicleModal").style.display = "flex";
}

function closeAddVehicleModal() {
    document.getElementById("addVehicleModal").style.display = "none";
}

document.addEventListener("DOMContentLoaded", function() {

    const typeSelect = document.getElementById("vehicleTypeSelect");
    if (typeSelect) {
        typeSelect.addEventListener("change", function() {
            const carFields = document.getElementById("carFields");
            const bikeFields = document.getElementById("bikeFields");

            if (this.value === "car") {
                if (carFields) carFields.style.display = "block";
                if (bikeFields) bikeFields.style.display = "none";
            } else {
                if (carFields) carFields.style.display = "none";
                if (bikeFields) bikeFields.style.display = "block";
            }
        });
    }

});
</script>

</body>
</html>
