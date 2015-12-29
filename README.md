# phpaudio
Audio Parsing and Processing Library

There currently exists no PHP library or code to work with audio file formats. The general solution is to use external libraries or softwares like ffmpeg, sox etc , by calling them with parameters and then capturing their output.

PHPAudio is an attempt to process audio files in pure PHP code without using any external dependencies. This is going to take at least a few months as audio processing is a very big field. For starters, I am going to focus on WAV and MP3 formats as they are the most commonly used and then move on to other formats like Ogg, AU, AIFF etc.

WAV files

The following types of WAV files are handled:

    Uncompressed PCM format
    IEEE Floating Point
    ALaw encoded
    muLaw encoded

1.Parse a WAV file and get information about it

Usage:

  require_once("clsWAV.php"); 
  
  $wav = new \phpaudio\WAV('/path to your wave file.wav');
  
  if ($wav->getError() != null && $wav->getError() != "") 
  
    echo($wav->getError() . "\n"); 
    
  else 
  
    var_dump($wav->getHeaderData()); 
    
    
    
  The WaveHeader class gets filled up with all the information about the WAV file. Among other things, it provides information about:

    WAV format type
    No.of Channels
    Sample Rate
    Byte Rate
    Bits per Sample
    No.of Samples
    Size of Each Sample
    Duration in Seconds or in HH:MM:SS format
    Bytes in Each Channel

Check the comments in clsWAV.php for more details.

    
