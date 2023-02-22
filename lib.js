function showInfo(imageDir, info) {
    //console.log(document.querySelectorAll('[id^="btatt-"]'));
    document.getElementById("hideOnHover").style.display = "none";
    var htmlString = "<small><span>" + info[0] + "</span>&nbsp;<span>" + info[2] + "</span></small>" +
    "<br><small>" + info[1] + "</small><img class='attendanceIcon' src='" + imageDir + info[1] + ".gif'>";
    document.getElementById("attendanceInfoBox").innerHTML = htmlString;
    document.getElementById("attendanceInfoBox").style.display = "block";
}
