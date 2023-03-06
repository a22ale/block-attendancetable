function showInfo(imageDir, info) {
    document.getElementById("hideOnHover").style.display = "none";
    var htmlString = "<small>" + info[0] + "</small><br><a href='" + info[4] + "'><small>" +
        "<span>" + info[3] + "</span></a>&nbsp;-&nbsp;<span>" + info[2] + "</span></small>&nbsp;" +
        "<img class='icon iconInInfo' alt='" + info[2] + "' title='" + info[2] + "' src='" + imageDir + info[1] + ".gif'>";
    document.getElementById("attendanceInfoBox").innerHTML = htmlString;
    document.getElementById("attendanceInfoBox").style.display = "block";
}

function onClick(url) {
    window.location.href = '../' + url;
}