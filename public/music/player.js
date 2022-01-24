const background = document.querySelector('#background'); // background derived from album cover below
const song = document.querySelector('#song'); // audio object

const songArtist = document.querySelector('.song-artist'); // element where track artist appears
const songTitle = document.querySelector('.song-title'); // element where track title appears
const progressBar = document.querySelector('#progress-bar'); // element where progress bar appears
let pPause = document.querySelector('#play-pause'); // element where play and pause image appears

songIndex = 0;
songs = ['/music/jingle-bells.mp3', '/music/god-rest-ye-merry-gentlemen.mp3', '/music/oh-come-all-ye-faithful.mp3', '/music/hark-the-herald-angels-sing.mp3', '/music/we-three-kings.mp3', '/music/here-we-come-a-caroling.mp3', '/music/joy-to-the-world.mp3', '/music/o-christmas-tree.mp3', '/music/away-in-a-manger.mp3', '/music/silent-night.mp3', '/music/the-first-noel.mp3', '/music/auld-lang-syne.mp3']; // object storing paths for audio objects
songArtists = ['The Hospice of Baton Rouge', 'The Hospice of Baton Rouge', 'The Hospice of Baton Rouge', 'The Hospice of Baton Rouge', 'The Hospice of Baton Rouge', 'The Hospice of Baton Rouge', 'The Hospice of Baton Rouge', 'The Hospice of Baton Rouge', 'The Hospice of Baton Rouge', 'The Hospice of Baton Rouge', 'The Hospice of Baton Rouge', 'The Hospice of Baton Rouge']; // object storing track artists
songTitles = ["Jingle Bells", "God Rest Ye Merry Gentlemen", "Oh Come All Ye Faithful", "Hark The Herald Angels Sing", "We Three Kings", "Here We Come a Caroling", "Joy To The World", "O Christmas Tree", "Away In A Manger", "Silent Night", "The First Noel", "Auld Lang Syne"]; // object storing track titles

// function where pp (play-pause) element changes based on playing boolean value - if play button clicked, change pp.src to pause button and call song.play() and vice versa.
let playing = true;
function playPause() {
    if (playing) {
        const song = document.querySelector('#song');
        document.querySelector('#play-pause').src = "/music/pause.png";
        


        song.play();
        playing = false;
    } else {
        document.querySelector('#play-pause').src = "/music/play.png";
        
        song.pause();
        playing = true;
    }
}

// automatically play the next song at the end of the audio object's duration
song.addEventListener('ended', function(){
    nextSong();
});

// function where songIndex is incremented, song/thumbnail image/background image/song artist/song title changes to next index value, and playPause() runs to play next track 
function nextSong() {
    songIndex++;
    if (songIndex > 1) {
        songIndex = 0;
    };
    song.src = songs[songIndex];

    songArtist.innerHTML = songArtists[songIndex];
    songTitle.innerHTML = songTitles[songIndex];

    playing = true;
    playPause();
}

// function where songIndex is decremented, song/thumbnail image/background image/song artist/song title changes to previous index value, and playPause() runs to play previous track 
function previousSong() {
    songIndex--;
    if (songIndex < 0) {
        songIndex = 1;
    };
    song.src = songs[songIndex];

    songArtist.innerHTML = songArtists[songIndex];
    songTitle.innerHTML = songTitles[songIndex];

    playing = true;
    playPause();
}

// update progressBar.max to song object's duration, same for progressBar.value, update currentTime/duration DOM
function updateProgressValue() {
    progressBar.max = song.duration;
    progressBar.value = song.currentTime;
    document.querySelector('.currentTime').innerHTML = (formatTime(Math.floor(song.currentTime)));
    if (document.querySelector('.durationTime').innerHTML === "NaN:NaN") {
        document.querySelector('.durationTime').innerHTML = "0:00";
    } else {
        document.querySelector('.durationTime').innerHTML = (formatTime(Math.floor(song.duration)));
    }
};

// convert song.currentTime and song.duration into MM:SS format
function formatTime(seconds) {
    let min = Math.floor((seconds / 60));
    let sec = Math.floor(seconds - (min * 60));
    if (sec < 10){ 
        sec  = `0${sec}`;
    };
    return `${min}:${sec}`;
};

// run updateProgressValue function every 1/2 second to show change in progressBar and song.currentTime on the DOM
setInterval(updateProgressValue, 500);

// function where progressBar.value is changed when slider thumb is dragged without auto-playing audio
function changeProgressBar() {
    song.currentTime = progressBar.value;
};