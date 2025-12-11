using System;
using System.IO;
using System.Media;
using System.Threading.Tasks;
using RfidAgent.Models;

namespace RfidAgent.Services
{
    public class AudioService
    {
        private readonly AppSettings _settings;

        public AudioService(AppSettings settings)
        {
            _settings = settings;
        }

        public void PlaySuccess()
        {
            if (!_settings.EnableSound) return;
            PlaySystemSound(SystemSounds.Asterisk); // Placeholder for mp3
        }

        public void PlayError()
        {
            if (!_settings.EnableSound) return;
            PlaySystemSound(SystemSounds.Hand);
        }

        private void PlaySystemSound(SystemSound sound)
        {
            try
            {
                // In production, load from .wav files:
                // new SoundPlayer("success.wav").Play();
                sound.Play();
            }
            catch { }
        }
    }
}
