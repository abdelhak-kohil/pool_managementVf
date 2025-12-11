using System;
using System.IO;
using RfidAgent.Models;

namespace RfidAgent.Services
{
    public class LoggerService
    {
        private static readonly object _lock = new object();
        private readonly string _logPath;
        private readonly AppSettings _settings;

        public LoggerService(AppSettings settings)
        {
            _settings = settings;
            string folder = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "logs");
            if (!Directory.Exists(folder)) Directory.CreateDirectory(folder);
            _logPath = Path.Combine(folder, $"log_{DateTime.Now:yyyy-MM-dd}.txt");
        }

        public void Log(string message, LogLevel level)
        {
            // if (level < _settings.MinLogLevel) return; // Temporarily disable level check for debugging

            try
            {
                lock (_lock)
                {
                    string line = $"[{DateTime.Now:HH:mm:ss}] [{level}] {message}";
                    File.AppendAllText(_logPath, line + Environment.NewLine);
                    
                    // Force Debug Output to Visual Studio Output Window
                    System.Diagnostics.Debug.WriteLine(line);
                }
            }
            catch (Exception ex) 
            {
                 System.Diagnostics.Debug.WriteLine($"LOGGER FAIL: {ex.Message}");
            }
        }

        public void Error(string message) => Log(message, LogLevel.Error);
        public void Warning(string message) => Log(message, LogLevel.Warning);
        public void Info(string message) => Log(message, LogLevel.Info);
        public void Debug(string message) => Log(message, LogLevel.Debug);

        public static string GetLogsFolderPath()
        {
             return Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "logs");
        }

        public static void ClearLogs()
        {
            try 
            {
                string folder = GetLogsFolderPath();
                if (Directory.Exists(folder))
                {
                    foreach (var file in Directory.GetFiles(folder, "*.txt"))
                    {
                        File.Delete(file);
                    }
                }
            }
            catch { }
        }
    }
}
