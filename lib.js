function showInfo(imageDir, info) {
    //console.log(document.querySelectorAll('[id^="btatt-"]'));
    document.getElementById("hideOnHover").style.display = "none";
    var htmlString = "<small>" + info[0] + "</small><br><small>" +
        "<span>" + info[2] + "</span>&nbsp;<span>" + info[1] + "</span></small>&nbsp;" +
        "<img class='attendanceIcon' alt='" + info[1] + "' title='" + info[1] + "' src='" + imageDir + info[1] + ".gif'>";
    document.getElementById("attendanceInfoBox").innerHTML = htmlString;
    document.getElementById("attendanceInfoBox").style.display = "block";
}