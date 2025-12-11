using System;
using System.IO;
using System.Security.Cryptography;
using System.Text;
using System.Text.Json;
using System.Text.Json.Serialization;

namespace RfidAgent.Models
{
    public enum RfidMode { COM, HID }
    public enum AppTheme { Light, Dark, BlueCorporate }
    public enum LogLevel { Error, Warning, Info, Debug }
    public enum LayoutPreset { Classic, ModernCards, MinimalKiosk }

    public class AppSettings
    {
        // A) General Settings
        public string AppName { get; set; } = "Gym RFID Agent";
        public string Language { get; set; } = "FR"; // FR, EN, AR
        public bool AutoStart { get; set; } = false; // Registry logic will be separate
        public bool AutoLoginAgent { get; set; } = true;
        public bool EnableSound { get; set; } = true;
        public AppTheme Theme { get; set; } = AppTheme.BlueCorporate;

        // B) Device Configuration
        public RfidMode Mode { get; set; } = RfidMode.COM;
        public string ComPort { get; set; } = "COM3";
        public int BaudRate { get; set; } = 9600;
        public int DataBits { get; set; } = 8;
        public string Parity { get; set; } = "None"; // None, Odd, Even, Mark, Space
        public string StopBits { get; set; } = "One"; // None, One, Two, OnePointFive

        // C) API / Server Configuration
        public string ApiBaseUrl { get; set; } = "https://pool-manager.local/api/v1";
        public string ApiKey { get; set; } = ""; // To be encrypted
        public string DeviceId { get; set; } = Guid.NewGuid().ToString();
        public int TimeoutSeconds { get; set; } = 30;
        public int Retries { get; set; } = 3;
        public int HeartbeatIntervalMinutes { get; set; } = 5;

        // D) Attendance Settings
        public bool EnableMemberCheckIn { get; set; } = true;
        public bool EnableStaffCheckIn { get; set; } = true;
        public bool SameRfidForBoth { get; set; } = true;
        public int CheckInGapMinutes { get; set; } = 5;
        public int AutoCheckoutHours { get; set; } = 0; // 0 = disabled

        // E) Offline Storage
        public bool EnableOfflineMode { get; set; } = true;
        public int MaxOfflineQueueSize { get; set; } = 1000;
        public int AutoSyncIntervalMinutes { get; set; } = 15;

        // F) Logging
        public LogLevel MinLogLevel { get; set; } = LogLevel.Info;

        // G) Security
        public string AdminPasswordHash { get; set; } = "8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918"; // SHA256 for 'admin'
        public int SessionTimeoutMinutes { get; set; } = 10;
        public bool PanicMode { get; set; } = false;

        // H) UI Customization
        public string CustomLogoPath { get; set; } = "";
        public string AccentColor { get; set; } = "#10B981";
        public int NotificationDurationSeconds { get; set; } = 3;
        public LayoutPreset ScreenLayout { get; set; } = LayoutPreset.Classic;

        // I) Updates
        public bool AutoUpdate { get; set; } = true;

        // Compatibility Properties (Backwards compatible with our previous code)
        [JsonIgnore]
        public string ReaderId 
        { 
            get => DeviceId; 
            set => DeviceId = value; 
        }

        private const string SettingsFileName = "settings.dat"; // Changed extension to imply binary/encrypted
        private static readonly byte[] Entropy = Encoding.UTF8.GetBytes("RfidAgent-Salt-2025");

        public void Save()
        {
            var options = new JsonSerializerOptions { WriteIndented = true };
            string json = JsonSerializer.Serialize(this, options);
            
            // DPAPI Encryption
            byte[] plainBytes = Encoding.UTF8.GetBytes(json);
            byte[] cipherBytes = ProtectedData.Protect(plainBytes, Entropy, DataProtectionScope.CurrentUser);
            
            File.WriteAllBytes(SettingsFileName, cipherBytes);
        }

        public static AppSettings Load()
        {
            if (File.Exists(SettingsFileName))
            {
                try
                {
                    byte[] cipherBytes = File.ReadAllBytes(SettingsFileName);
                    
                    // Try Decrypt (DPAPI)
                    byte[] plainBytes;
                    try 
                    {
                        plainBytes = ProtectedData.Unprotect(cipherBytes, Entropy, DataProtectionScope.CurrentUser);
                    }
                    catch 
                    {
                        // Fallback: Try reading as plain JSON (migration from old version)
                        // If it fails, it throws, which is caught below
                        string text = Encoding.UTF8.GetString(cipherBytes);
                        return JsonSerializer.Deserialize<AppSettings>(text) ?? new AppSettings();
                    }

                    string json = Encoding.UTF8.GetString(plainBytes);
                    var loaded = JsonSerializer.Deserialize<AppSettings>(json);
                    return loaded ?? new AppSettings();
                }
                catch
                {
                    // Fallback to default if corrupted or decryption fails (different user)
                    return new AppSettings();
                }
            }
            // Migration check: check for old settings.json
            else if (File.Exists("settings.json"))
            {
                 try
                {
                    string json = File.ReadAllText("settings.json");
                    var old = JsonSerializer.Deserialize<AppSettings>(json);
                    return old ?? new AppSettings();
                }
                catch { }
            }
            return new AppSettings();
        }
    }
}
