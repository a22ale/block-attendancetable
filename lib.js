function showInfo(imageDir, info) {
    var table = '<table>' +
        '<tbody>' +
        '<tr>' +
        '<th rowspan = "2" scope = "rowgroup"><img src="' + imageDir + 'attendance.png"></th>' +
        '<td scope="row"><small>' + info.sessiondate + '</small></td>' +
        '</tr>' +
        '<tr>' +
        '<td scope="row"><a href="' + info.attendanceurllong + '"><small>' + info.attendancename + '</a></small></td>' +
        '<td><small><span>' + info.attendance + '</span></small> <img class="icon iconInInfo" alt="' + info.attendance + '" title="' + 
            info.attendance + '" src="' + imageDir + info.attendanceenglish + '.gif"></td>' +
        '</tr>' +
        '</tbody>' +
        '</table> ';
    document.getElementById("hideOnHover").style.display = "none";
    document.getElementById("attendanceInfoBox").innerHTML = table;
    document.getElementById("attendanceInfoBox").style.display = "block";
}

function onClick(url) {
    window.location.href = '../' + url;
}

function showInfoDashboard(imageDir, info, pos) {
    //document.getElementById("hideOnHover-" + pos).style.display = "none";
    var messages = document.getElementsByClassName("hideOnHover");
    Array.prototype.forEach.call(messages, function(el) {
        el.style.display = "none";
    });
    var table = '<table>' +
    '<tbody>' +
    '<tr>' +
    '<th class="attendanceIcon" rowspan = "2" scope = "rowgroup"><img src="' + imageDir + 'attendance.png"></th>' +
    '<td scope="row"><small>' + info.sessiondate + '</small></td>' +
    '</tr>' +
    '<tr>' +
    '<td scope="row"><a href="' + info.attendanceurllong + '"><small>' + info.attendancename + '</a></small></td>' +
    '<td><small><span>' + info.attendance + '</span></small> <img class="icon iconInInfo" alt="' + info.attendance + '" title="' + 
        info.attendance + '" src="' + imageDir + info.attendanceenglish + '.gif"></td>' +
    '</tr>' +
    '</tbody>' +
    '</table> ';
    var infoBoxes = document.getElementsByClassName("attendanceInfoBox");
    Array.prototype.forEach.call(infoBoxes, function(el) {
        el.style.display = "none";
    })
    document.getElementById("attendanceInfoBox-" + pos).innerHTML = table;
    document.getElementById("attendanceInfoBox-" + pos).style.display = "block";
}