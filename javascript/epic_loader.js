//This is to simulate realistic loading
function finishLoading() {
    document.getElementById("progress-bar").style.width = "100%";
    setTimeout(() => {
        document.getElementById("progress-bar").style.width = "0%";
    }, 500);
}
function startLoading() {
    setTimeout(() => {
        document.getElementById("progress-bar").style.width = "15%";
    }, 300)                   
    setTimeout(finishLoading, 1000);
}
startLoading();

function openAdd(){
    document.getElementById("new_project").style.display = "flex";
    document.getElementById("new_project").style.zIndex = "9";
}

function closeForm(){
    document.getElementById("new_project").style.display = "none";
}