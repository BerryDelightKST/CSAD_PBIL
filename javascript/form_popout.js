const targets = document.getElementsByClassName("card");
function openFormL(){
    document.getElementById("login").style.display = "flex";
    for (let i=0;i<targets.length;i++){
        targets[i].style.display= "none";
    }
}

function closeForm(){
    document.getElementById("login").style.display = "none";
    document.getElementById("signup").style.display = "none";
    document.getElementById("about").style.display = "none";
    for (let i=0;i<targets.length;i++){
        targets[i].style.display= "block";
    }
}
function openFormR(){
    document.getElementById("signup").style.display = "flex";
    for (let i=0;i<targets.length;i++){
        targets[i].style.display= "none";
    }
}
function openAbout(){
    document.getElementById("about").style.display = "flex";
    for (let i=0;i<targets.length;i++){
        targets[i].style.display= "none";
    }
}
