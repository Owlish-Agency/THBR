function printData()
{
   var divToPrint=document.getElementById("printTable");
   newWin= window.open("");
   newWin.document.write(divToPrint.outerHTML);
   newWin.print();
   newWin.close();
}

// Play Video
function playVideo(player) {
    player.play();
}

// Pause Video
function pauseVideo(player) {
    player.pause();
}

// Hide Placeholder
function hidePlaceholder(banner) {
    $('.responsive-media', banner).css({paddingBottom: ''});
    $('.content-overlay', banner).hide();
    $('.js-startingCopy', banner).hide();
    $('.video-embed__placeholder', banner).hide();
    $('.video-embed__video', banner).show();
    banner.prepend('<a href="" class="close-video" uk-icon="icon: close"></a>');
}

// Show Placeholder
function showPlaceholder(banner) {
    $('.video-embed__video', banner).hide();
    $('.content-overlay', banner).show();
    $('.js-endingCopy', banner).show();
    $('.video-embed__placeholder', banner).show();
    $('.close-video', banner).remove();
}
/* Update total Time input field on Volunteer Timesheet Form */
function updateTotalTime(startTime, endTime) {
    var totalStartTime = moment(startTime, "hh:mm:ss a");
    var totalEndTime = moment(endTime, "hh:mm:ss a");
    console.log(totalStartTime);
    console.log(totalEndTime);
    var mins = (moment.utc(moment(totalEndTime, "hh:mm").diff(moment(totalStartTime, "hh:mm"))).format("mm"))/60;
    var totalHours = totalEndTime.diff(totalStartTime, 'hours') + mins;
    $('#totalVolunteeredHours').val(totalHours);
    console.log(totalHours);
}
function downloadCSV(csv, filename) {
    var csvFile;
    var downloadLink;

    // CSV file
    csvFile = new Blob([csv], {type: "text/csv"});

    // Download link
    downloadLink = document.createElement("a");

    // File name
    downloadLink.download = filename;

    // Create a link to the file
    downloadLink.href = window.URL.createObjectURL(csvFile);

    // Hide download link
    downloadLink.style.display = "none";

    // Add the link to DOM
    document.body.appendChild(downloadLink);

    // Click download link
    downloadLink.click();
}
function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("table tr");

    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");

        for (var j = 0; j < cols.length; j++)
            row.push(cols[j].innerText);

        csv.push(row.join(","));
    }

    // Download CSV file
    downloadCSV(csv.join("\n"), filename);
}

$(window).load(function() {
    // Animate loader off screen
    $(".se-pre-con").fadeOut("slow");
    if (Modernizr.videoautoplay) {
        // works like a charm
    } else {
        $('iframe').each(function() {
            var val = $(this).attr('src').replace('?background=1', '?background=0');
            $(this).attr('src', val);
        });
    }
});

$(window).resize(function(){
    $('iframe#iframe').css('height', height);
});

$(document).ready(function(){
    $(".uk-phone").inputmask({"mask": "(999) 999-9999"}); //specifying options
    // Initiate Slideshow
    //var slideshow = UIkit.slideshow($('#js-bannerCarousel'), {autoplay: true, videoautoplay: false, animation: 'swipe'});

    // Play Video
    $('.video-embed').on('click', '.js-play', function(event){
        // Get Iframe
        var container = $(this);
        var banner = container.closest('.video-embed');
        var iframe = $('iframe', banner);
        var player = new Vimeo.Player(iframe);
        hidePlaceholder(banner); // Hide Placeholder
        playVideo(player); // Play Video
        event.preventDefault();
        // If inside slideshow, pause slideshow
        if (container.parents('#js-bannerCarousel').length == 1) {
            slideshow.stop();
        }
        // When video ends, add back placeholder
        player.on('ended', function() {
            if (container.parents('#js-bannerCarousel').length == 1) {
                slideshow.start();
            }
            showPlaceholder(banner); // Show Placeholder
        });

    });

    // Close Video
    $('.video-embed').on('click', '.close-video', function(event){
        // Get Iframe
        var container = $(this);
        var banner = container.closest('.video-embed');
        var iframe = $('iframe', banner);
        var player = new Vimeo.Player(iframe);

        // If inside slideshow, start slideshow
        // Must go before removal of placeholder
        if (container.parents('#js-bannerCarousel').length == 1) {
            slideshow.start();
        }

        showPlaceholder(banner); // Show Placeholder
        pauseVideo(player); // Pause Video
        event.preventDefault();
    });

    // Set height of iframes
    var height = window.innerHeight;
    $('iframe#iframe').css('height', height);


    if ($('body').hasClass('volunteer-timesheet')) {
        $('.datepicker').pickadate({
          labelMonthNext: 'Go to the next month',
          labelMonthPrev: 'Go to the previous month',
          labelMonthSelect: 'Pick a month from the dropdown',
          labelYearSelect: 'Pick a year from the dropdown',
          selectMonths: true,
          selectYears: true,
          formatSubmit: "mm/dd/yyyy"
        });

        var startTime = "";
        var endTime = "";


        /* Initiate Start Time and End Time for Volunteer Timesheet */
        var from_$input = $('#startTime').pickatime({
            disable: [
                { from: [0,0], to: [0,45] }
            ],
            interval: 15,
            onSet: function( event ) {
                if ( event.select ) {
                    startTime = this.get();
                    console.log(startTime);
                    if (startTime.length && endTime.length)
                    {
                        updateTotalTime(startTime, endTime);
                    }
                }
            }
        }),
            from_picker = from_$input.pickatime('picker')

        var to_$input = $('#endTime').pickatime({
            disable: [
                { from: [0,0], to: [0,45] }
            ],
            interval: 15,
            onSet: function( event ) {
                if ( event.select ) {
                    endTime = this.get();
                    if (startTime.length && endTime.length)
                    {
                        updateTotalTime(startTime, endTime);
                    }
                }
            },
            formatLabel: function( timeObject ) {
                var minObject = this.get( 'min' ),

                    hours = (timeObject.hour - minObject.hour),

                    mins = ( timeObject.mins - minObject.mins ) / 60,

                    pluralize = function( number, word ) {
                        return number + ' ' + ( number === 1 ? word : word + 's' )
                    }
                return '<b>h</b>:i <!i>a</!i> <sm!all>(' + pluralize( hours + mins, '!hour' ) + ')</sm!all>'
            }
        }),
        to_picker = to_$input.pickatime('picker')


        // Check if there’s a “from” or “to” time to start with.
        if ( from_picker.get('value') ) {
          to_picker.set('min', from_picker.get('select'))
        }
        if ( to_picker.get('value') ) {
          from_picker.set('max', to_picker.get('select') )
        }

        // When something is selected, update the “from” and “to” limits.
        from_picker.on('set', function(event) {
          if ( event.select ) {
            to_picker.set('min', from_picker.get('select'))
          }
        })
        to_picker.on('set', function(event) {
          if ( event.select ) {
            from_picker.set('max', to_picker.get('select'))
          }
        })
    }
});