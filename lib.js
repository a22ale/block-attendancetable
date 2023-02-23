function showInfo(imageDir, info) {
    //console.log(document.querySelectorAll('[id^="btatt-"]'));
    document.getElementById("hideOnHover").style.display = "none";
    var htmlString = "<small>" + info[0] + "</small><br><small>" +
        "<span>" + info[3] + "</span>&nbsp;-&nbsp;<span>" + info[2] + "</span></small>&nbsp;" +
        "<img class='attendanceIcon' alt='" + info[2] + "' title='" + info[2] + "' src='" + imageDir + info[1] + ".gif'>";
    document.getElementById("attendanceInfoBox").innerHTML = htmlString;
    document.getElementById("attendanceInfoBox").style.display = "block";
}