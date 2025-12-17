using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using RfidAgent.Models;
using RfidAgent.Services;
using System;
using System.Threading.Tasks;
using System.Windows.Media;
using System.Windows;
using RfidAgent;

namespace RfidAgent.ViewModels
{
    public partial class MainViewModel : ObservableObject
    {
        private readonly RfidListenerService _rfidService;
        private readonly ILaravelApiService _apiService;
        private AppSettings _settings;

        // Visual States
        public enum AppState { Idle, Loading, Success, Error, Warning }

        [ObservableProperty]
        private AppState _currentState = AppState.Idle;

        [ObservableProperty]
        [NotifyPropertyChangedFor(nameof(HasMember))]
        private Member? _currentMember;

        public bool HasMember => CurrentMember != null;

        public bool IsFeedbackActive => CurrentState == AppState.Success || CurrentState == AppState.Error || CurrentState == AppState.Warning;

        [ObservableProperty]
        private string _statusMessage = "Veuillez scanner votre badge";

        [ObservableProperty]
        private string _statusSubMessage = "";

        [ObservableProperty]
        private string _backgroundColor = "#FFFFFF"; // Default White

        // Status Indicators
        [ObservableProperty]
        private string _systemStatus = "Offline";

        [ObservableProperty]
        private Brush _systemStatusColor = Brushes.Red;

        [ObservableProperty]
        private string _readerStatus = "Inactive";

        [ObservableProperty]
        private Brush _readerStatusColor = Brushes.Gray;

        [ObservableProperty]
        private bool _isBusy;

        private readonly AudioService _audioService;

        private readonly LoggerService _logger; // New Logger

        public MainViewModel(Services.HeartbeatService? heartbeatService)
        {
            // In a real app, DI container would inject these
            _settings = AppSettings.Load(); 
            _logger = new LoggerService(_settings); // Initialize Logger
            
            _rfidService = new RfidListenerService();
            _apiService = new LaravelApiService(new System.Net.Http.HttpClient(), _settings);
            _audioService = new AudioService(_settings);

            _rfidService.OnBadgeScanned += OnBadgeScanned;
            _rfidService.OnConnectionStatusChanged += (s, connected) => UpdateReaderStatus(connected);

            // Heartbeat
            if (heartbeatService != null)
            {
                heartbeatService.OnStatusChanged += (s, online) => UpdateSystemStatus(online);
                UpdateSystemStatus(heartbeatService.IsOnline);
            }
            
            StartRfidService();
            _logger.Info("Application Started");
        }

        private void UpdateSystemStatus(bool online)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                SystemStatus = online ? "System Online" : "System Offline";
                SystemStatusColor = online ? new SolidColorBrush((Color)ColorConverter.ConvertFromString("#10B981")) : Brushes.Red;
            });
        }

        private void UpdateReaderStatus(bool connected)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                ReaderStatus = connected ? "Reader Active" : "Reader Inactive";
                ReaderStatusColor = connected ? new SolidColorBrush((Color)ColorConverter.ConvertFromString("#10B981")) : Brushes.Gray;
            });
        }

        private void StartRfidService()
        {
             if (_settings.PanicMode) return; // Security feature
             if (_settings.Mode == RfidMode.COM && !string.IsNullOrEmpty(_settings.ComPort) && _settings.ComPort != "No Ports Found")
             {
                 _rfidService.Start(_settings.ComPort, _settings.BaudRate, _settings.DataBits, _settings.Parity, _settings.StopBits);
             }
        }

        private void OnBadgeScanned(object? sender, string uid)
        {
            if (_settings.PanicMode) return;

            System.Windows.Application.Current.Dispatcher.Invoke(() => 
            {
                ProcessScanCommand.Execute(uid);
            });
        }

        [RelayCommand]
        public async Task ProcessScanAsync(string uid)
        {
            if (IsBusy || _settings.PanicMode) return;
            
            IsBusy = true;
            SetState(AppState.Loading);
            _logger.Info($"Processing Scan: {uid} (Device: {_settings.DeviceId})");

            try
            {
                var result = await _apiService.CheckInMemberAsync(uid, _settings.DeviceId);

                if (result.Success)
                {
                    CurrentMember = result.Member;
                    SetState(AppState.Success);
                    _audioService.PlaySuccess();
                    _logger.Info($"Access Granted for {result.Member?.FullName}");
                }
                else
                {
                    CurrentMember = result.Member; 
                    SetState(AppState.Error, "Access Denied", result.ErrorMessage);
                    _audioService.PlayError();
                    _logger.Warning($"Access Denied for {uid}: {result.ErrorMessage}");
                }
            }
            catch (Exception ex)
            {
                _logger.Error($"Error processing scan: {ex.Message}");
                SetState(AppState.Error, "Network Error", ex.Message);
                _audioService.PlayError();
            }
            finally
            {
                IsBusy = false;
                await Task.Delay(_settings.NotificationDurationSeconds * 1000);
                SetState(AppState.Idle);
            }
        }

        [RelayCommand]
        public void OpenSettings()
        {
            // Security Check
            var login = new RfidAgent.Views.LoginWindow();
            login.Owner = Application.Current.MainWindow;
            login.ShowDialog();

            if (login.IsAuthenticated)
            {
                var settingsWindow = new RfidAgent.Views.SettingsWindow();
                settingsWindow.ShowDialog();
                
                // Reload settings after close
                _settings = AppSettings.Load();
                
                // Re-apply critical settings
                StartRfidService();
            }
        }

        [RelayCommand]
        public void OpenManualEntry()
        {
            var manual = new RfidAgent.Views.ManualScanWindow();
            manual.Owner = Application.Current.MainWindow;
            if (manual.ShowDialog() == true)
            {
                ProcessScanCommand.Execute(manual.ScannedCode);
            }
        }

        private void SetState(AppState state, string message = "", string subMessage = "")
        {
            CurrentState = state;
            OnPropertyChanged(nameof(IsFeedbackActive));
            switch (state)
            {
                case AppState.Idle:
                    StatusMessage = "Veuillez scanner votre badge";
                    StatusSubMessage = "";
                    BackgroundColor = "#FFFFFF";
                    CurrentMember = null;
                    break;
                case AppState.Loading:
                    StatusMessage = "Checking...";
                    StatusSubMessage = "";
                    BackgroundColor = "#F3F4F6"; // Gray
                    break;
                case AppState.Success:
                    StatusMessage = "Access Granted";
                    StatusSubMessage = "";
                    BackgroundColor = "#10B981"; // Emerald Green
                    break;
                case AppState.Error:
                    StatusMessage = message;
                    StatusSubMessage = subMessage;
                    BackgroundColor = "#E11D48"; // Rose Red
                    break;
                case AppState.Warning:
                    StatusMessage = message;
                    StatusSubMessage = subMessage;
                    BackgroundColor = "#F59E0B"; // Amber
                    break;
            }
        }
    }
}
