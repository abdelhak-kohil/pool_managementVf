using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using RfidAgent.Models;
using RfidAgent.Services;
using System;
using System.Collections.ObjectModel;
using System.IO.Ports;
using System.Linq;
using System.Windows;
using System.Collections.Generic;

namespace RfidAgent.ViewModels
{
    public partial class SettingsViewModel : ObservableObject
    {
        private AppSettings _currentSettings;

        // A) General
        [ObservableProperty] private string _appName = "Gym RFID Agent";
        [ObservableProperty] private string _selectedLanguage = "FR";
        [ObservableProperty] private bool _autoStart;
        [ObservableProperty] private bool _enableSound;
        [ObservableProperty] private AppTheme _selectedTheme;

        // B) Device
        [ObservableProperty] private RfidMode _selectedMode;
        [ObservableProperty] private string _selectedComPort = "COM3";
        [ObservableProperty] private int _selectedBaudRate;
        [ObservableProperty] private string _selectedParity = "None";
        [ObservableProperty] private string _selectedStopBits = "One";
        [ObservableProperty] private int _selectedDataBits;

        // C) API
        [ObservableProperty] private string _apiBaseUrl = "";
        [ObservableProperty] private string _apiKey = "";
        [ObservableProperty] private string _deviceId = "";
        [ObservableProperty] private int _timeoutSeconds;

        // D) Attendance
        [ObservableProperty] private bool _enableMemberCheckIn;
        [ObservableProperty] private bool _enableStaffCheckIn;
        [ObservableProperty] private bool _sameRfidForBoth;
        [ObservableProperty] private int _checkInGapMinutes;

        // E) Offline
        [ObservableProperty] private bool _enableOfflineMode;
        [ObservableProperty] private int _maxOfflineQueueSize;

        // F) Logging
        [ObservableProperty] private LogLevel _selectedLogLevel;

        // G) Security
        [ObservableProperty] private bool _panicMode;

        // H) UI
        [ObservableProperty] private string _accentColor = "#10B981";
        [ObservableProperty] private LayoutPreset _selectedLayout;

        public ObservableCollection<string> AvailableComPorts { get; } = new();
        public ObservableCollection<int> AvailableBaudRates { get; } = new() { 9600, 19200, 38400, 57600, 115200 };
        public ObservableCollection<string> Languages { get; } = new() { "FR", "EN", "AR" };
        public List<AppTheme> Themes { get; } = Enum.GetValues(typeof(AppTheme)).Cast<AppTheme>().ToList();
        public List<RfidMode> RfidModes { get; } = Enum.GetValues(typeof(RfidMode)).Cast<RfidMode>().ToList();
        public List<LogLevel> LogLevels { get; } = Enum.GetValues(typeof(LogLevel)).Cast<LogLevel>().ToList();
        public List<LayoutPreset> LayoutPresets { get; } = Enum.GetValues(typeof(LayoutPreset)).Cast<LayoutPreset>().ToList();
        public List<string> ParityOptions { get; } = new() { "None", "Odd", "Even", "Mark", "Space" };
        public List<string> StopBitsOptions { get; } = new() { "None", "One", "Two", "OnePointFive" };
        public List<int> DataBitsOptions { get; } = new() { 7, 8 };


        private readonly LoggerService _logger; // Local logger for Settings

        public SettingsViewModel()
        {
            _currentSettings = AppSettings.Load();
            _logger = new LoggerService(_currentSettings);
            
            LoadFromSettings();
            RefreshComPorts();
            LoadLogs();
        }

        // ... existing codes ...



        private void LoadFromSettings()
        {
            AppName = _currentSettings.AppName;
            SelectedLanguage = _currentSettings.Language;
            AutoStart = _currentSettings.AutoStart;
            EnableSound = _currentSettings.EnableSound;
            SelectedTheme = _currentSettings.Theme;

            SelectedMode = _currentSettings.Mode;
            SelectedComPort = _currentSettings.ComPort;
            SelectedBaudRate = _currentSettings.BaudRate;
            SelectedParity = _currentSettings.Parity;
            SelectedStopBits = _currentSettings.StopBits;
            SelectedDataBits = _currentSettings.DataBits;

            ApiBaseUrl = _currentSettings.ApiBaseUrl;
            ApiKey = _currentSettings.ApiKey;
            DeviceId = _currentSettings.DeviceId;
            TimeoutSeconds = _currentSettings.TimeoutSeconds;

            EnableMemberCheckIn = _currentSettings.EnableMemberCheckIn;
            EnableStaffCheckIn = _currentSettings.EnableStaffCheckIn;
            SameRfidForBoth = _currentSettings.SameRfidForBoth;
            CheckInGapMinutes = _currentSettings.CheckInGapMinutes;

            EnableOfflineMode = _currentSettings.EnableOfflineMode;
            MaxOfflineQueueSize = _currentSettings.MaxOfflineQueueSize;

            SelectedLogLevel = _currentSettings.MinLogLevel;
            PanicMode = _currentSettings.PanicMode;

            AccentColor = _currentSettings.AccentColor;
            SelectedLayout = _currentSettings.ScreenLayout;

            if (!AvailableComPorts.Contains(SelectedComPort)) SelectedComPort = AvailableComPorts.FirstOrDefault() ?? "COM3";
        }

        private void RefreshComPorts()
        {
            AvailableComPorts.Clear();
            foreach (var port in SerialPort.GetPortNames())
            {
                AvailableComPorts.Add(port);
            }
            if (!AvailableComPorts.Any()) AvailableComPorts.Add("No Ports Found");
        }

        [RelayCommand]
        public void Save()
        {
            _currentSettings.AppName = AppName;
            _currentSettings.Language = SelectedLanguage;
            _currentSettings.AutoStart = AutoStart;
            _currentSettings.EnableSound = EnableSound;
            _currentSettings.Theme = SelectedTheme;

            _currentSettings.Mode = SelectedMode;
            _currentSettings.ComPort = SelectedComPort;
            _currentSettings.BaudRate = SelectedBaudRate;
            _currentSettings.Parity = SelectedParity;
            _currentSettings.StopBits = SelectedStopBits;
            _currentSettings.DataBits = SelectedDataBits;

            _currentSettings.ApiBaseUrl = ApiBaseUrl;
            _currentSettings.ApiKey = ApiKey;
            _currentSettings.DeviceId = DeviceId;
            _currentSettings.TimeoutSeconds = TimeoutSeconds;

            _currentSettings.EnableMemberCheckIn = EnableMemberCheckIn;
            _currentSettings.EnableStaffCheckIn = EnableStaffCheckIn;
            _currentSettings.SameRfidForBoth = SameRfidForBoth;
            _currentSettings.CheckInGapMinutes = CheckInGapMinutes;

            _currentSettings.EnableOfflineMode = EnableOfflineMode;
            _currentSettings.MaxOfflineQueueSize = MaxOfflineQueueSize;

            _currentSettings.MinLogLevel = SelectedLogLevel;
            _currentSettings.PanicMode = PanicMode;

            _currentSettings.AccentColor = AccentColor;
            _currentSettings.ScreenLayout = SelectedLayout;

            _currentSettings.Save();

            // Set Application Auto-Start registry key
            try
            {
                string keyName = "RfidAgent";
                string? assemblyLocation = System.Environment.ProcessPath; // .NET 6+
                
                if (!string.IsNullOrEmpty(assemblyLocation))
                {
                    using var key = Microsoft.Win32.Registry.CurrentUser.OpenSubKey(@"SOFTWARE\Microsoft\Windows\CurrentVersion\Run", true);
                    if (AutoStart)
                    {
                        key?.SetValue(keyName, $"\"{assemblyLocation}\"");
                    }
                    else
                    {
                        key?.DeleteValue(keyName, false);
                    }
                }
            }
            catch (Exception ex)
            {
                // Can happen if missing permissions, but HKCU usually allows write
                System.Diagnostics.Debug.WriteLine($"AutoStart Error: {ex.Message}");
            }

            MessageBox.Show("Settings Saved Successfully!", "Saved", MessageBoxButton.OK, MessageBoxImage.Information);
            
            Close();
        }


        
        [RelayCommand]
        public void Close()
        {
            foreach (Window window in Application.Current.Windows)
            {
                if (window.DataContext == this)
                {
                    window.Close();
                }
            }
        }

        [ObservableProperty]
        private string _logContent = "Loading logs...";

        private void LoadLogs()
        {
            try
            {
                string folder = RfidAgent.Services.LoggerService.GetLogsFolderPath();
                string file = System.IO.Path.Combine(folder, $"log_{DateTime.Now:yyyy-MM-dd}.txt");
                if (System.IO.File.Exists(file))
                {
                    // Read last 100 lines
                    var lines = System.IO.File.ReadLines(file).Reverse().Take(100).Reverse();
                    LogContent = string.Join(Environment.NewLine, lines);
                }
                else
                {
                    LogContent = "No logs found for today.";
                }
            }
            catch (Exception ex)
            {
                LogContent = $"Error loading logs: {ex.Message}";
            }
        }

        [RelayCommand]
        public void OpenLogsFolder()
        {
            try
            {
                string path = RfidAgent.Services.LoggerService.GetLogsFolderPath();
                if (!System.IO.Directory.Exists(path)) System.IO.Directory.CreateDirectory(path);
                System.Diagnostics.Process.Start("explorer.exe", path);
            }
            catch { }
        }

        [RelayCommand]
        public void ClearLogs()
        {
            if (MessageBox.Show("Are you sure you want to delete all log files?", "Confirm", MessageBoxButton.YesNo, MessageBoxImage.Warning) == MessageBoxResult.Yes)
            {
                RfidAgent.Services.LoggerService.ClearLogs();
                LoadLogs();
                MessageBox.Show("Logs Cleared.", "Info", MessageBoxButton.OK, MessageBoxImage.Information);
            }
        }
        
        [RelayCommand]
        public void RefreshLogs()
        {
            LoadLogs();
        }

        [RelayCommand]
        public async System.Threading.Tasks.Task TestConnection()
        {
            try 
            {
                using var client = new System.Net.Http.HttpClient();
                client.Timeout = TimeSpan.FromSeconds(TimeoutSeconds);
                if (!string.IsNullOrEmpty(ApiKey)) client.DefaultRequestHeaders.Add("X-API-KEY", ApiKey);
                
                _logger.Info($"Testing Connectivity to: {ApiBaseUrl}");
                var response = await client.GetAsync(ApiBaseUrl);
                
                if (response.IsSuccessStatusCode)
                {
                    _logger.Info($"Test Connection Successful: {response.StatusCode}");
                    MessageBox.Show($"Connected! Status: {response.StatusCode}", "Test API", MessageBoxButton.OK, MessageBoxImage.Information);
                }
                else
                {
                    _logger.Warning($"Test Connection returned: {response.StatusCode}");
                    MessageBox.Show($"Connected but status: {response.StatusCode}", "Test API", MessageBoxButton.OK, MessageBoxImage.Warning);
                }
            }
            catch (Exception ex)
            {
                 _logger.Error($"Test Connection Failed: {ex.Message}");
                 MessageBox.Show($"Failed: {ex.Message}", "Test API", MessageBoxButton.OK, MessageBoxImage.Error);
            }
            finally 
            {
                RefreshLogs();
            }
        }
    }
}
