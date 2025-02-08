function validateDates() {
    var startDate = document.getElementsByName("start_date")[0].value;
    var endDate = document.getElementsByName("end_date")[0].value;

    if (new Date(startDate) > new Date(endDate)) {
        alert("End date cannot be before start date.");
        return false; // Prevent form submission
    }
    return true; // Allow form submission
}

function feedback(){
    alert('User Assigned');
}