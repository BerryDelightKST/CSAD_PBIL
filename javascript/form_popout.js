
function openFormL(){
    document.getElementById("login").style.display = "block";
    /*let targets = document.querySelectorAll(".card");
    targets.forEach(element => {
        element.style.display= "none";
    });*/
}

function closeForm(){
    document.getElementById("login").style.display = "none";
    document.getElementById("signup").style.display = "none";
    document.getElementById("about").style.display = "none";
    targets.forEach(element => {
        element.style.display= "block";
    });
}
function openFormR(){
    document.getElementById("signup").style.display = "block";
}
function openAbout(){
    document.getElementById("about").style.display = "block";
}