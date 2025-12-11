using System;
using System.Net.Http;
using System.Net.Http.Json;
using System.Threading;
using System.Threading.Tasks;
using RfidAgent.Models;

namespace RfidAgent.Services
{
    public class HeartbeatService : IDisposable
    {
        private readonly HttpClient _httpClient;
        private readonly AppSettings _settings;
        private readonly LoggerService? _logger;
        private Timer? _timer;
        private bool _isRunning;
        
        public bool IsOnline { get; private set; }
        public event EventHandler<bool>? OnStatusChanged;

        public HeartbeatService(HttpClient httpClient, AppSettings settings, LoggerService? logger = null)
        {
            _httpClient = httpClient;
            _settings = settings;
            _logger = logger;
        }

        public void Start()
        {
            if (_isRunning) return;
            _isRunning = true;
            _logger?.Info("Heartbeat Service Started");
            
            // Start timer: 0 delay, 30s interval
            _timer = new Timer(SendHeartbeat, null, TimeSpan.Zero, TimeSpan.FromSeconds(30));
        }

        public void Stop()
        {
            _isRunning = false;
            _timer?.Change(Timeout.Infinite, 0);
            _logger?.Info("Heartbeat Service Stopped");
        }

        private async void SendHeartbeat(object? state)
        {
            if (!_isRunning) return;

            try
            {
                var payload = new
                {
                    device_id = _settings.DeviceId,
                    version = "1.0.0", // Could be pulled from Assembly
                    ip_address = "" // Server will detect IP
                };

                string baseUrl = _settings.ApiBaseUrl.TrimEnd('/');
                string url = $"{baseUrl}/v1/heartbeat";

                var request = new HttpRequestMessage(HttpMethod.Post, url);
                request.Content = JsonContent.Create(payload);
                request.Headers.Add("X-API-KEY", _settings.ApiKey);
                request.Headers.Add("Accept", "application/json");

                var response = await _httpClient.SendAsync(request);
                
                if (!response.IsSuccessStatusCode)
                {
                    string content = await response.Content.ReadAsStringAsync();
                    _logger?.Warning($"Heartbeat Failed: {response.StatusCode} - {content}");
                    UpdateStatus(false);
                }
                else
                {
                    UpdateStatus(true);
                }
            }
            catch (Exception ex)
            {
                _logger?.Error($"Heartbeat Error: {ex.Message}");
                UpdateStatus(false);
            }
        }

        private void UpdateStatus(bool isOnline)
        {
            if (IsOnline != isOnline)
            {
                IsOnline = isOnline;
                OnStatusChanged?.Invoke(this, isOnline);
            }
        }

        public void Dispose()
        {
            _timer?.Dispose();
        }
    }
}
