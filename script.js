let partCount = 1;

function addPart() {
    partCount++;
    const partsContainer = document.getElementById('partsContainer');
    const newPart = `
        <div class="part">
            <div class="form-group">
                <label for="part_name_${partCount}">Part ${partCount} Name:</label>
                <input type="text" class="form-control" id="part_name_${partCount}" name="part_name[]" required>
                <label for="part_description_${partCount}">Part ${partCount} Description:</label>
                <textarea class="form-control" id="part_description_${partCount}" name="part_description[]" rows="2"></textarea>
            </div>
        </div>
    `;
    partsContainer.insertAdjacentHTML('beforeend', newPart);
}

function removePart() {
    if (partCount > 1) {
        const partsContainer = document.getElementById('partsContainer');
        partsContainer.removeChild(partsContainer.lastElementChild);
        partCount--;
    }
}


function checkUser(user_name, callback) {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            callback(response.exists);
        }
    };
    xhr.open('GET', 'check_user.php?user_name=' + encodeURIComponent(user_name), true);
    xhr.send();
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired.');

    // Check file extension before form submission
    document.getElementById('taskForm').addEventListener('submit', function(event) {
        var fileInput = document.getElementById('file');
        var filePath = fileInput.value;
        var allowedExtensions = /(\.pdf|\.docx)$/i;

        if (!allowedExtensions.exec(filePath)) {
            event.preventDefault();
            document.getElementById('message').innerHTML = '<div class="alert alert-danger">Invalid file type. Please upload a PDF or DOCX file.</div>';
            fileInput.value = '';
            return false;
        } else {
            document.getElementById('message').innerHTML = '';
        }
    });
});