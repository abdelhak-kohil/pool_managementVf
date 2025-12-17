using System.Threading.Tasks;
using System.Net.Http.Json;
using System.Text.Json;
using System.Text.Json.Serialization;
using RfidAgent.Models;

namespace RfidAgent.Services
{
    public interface ILaravelApiService
    {
        Task<(bool Success, Member? Member, string ErrorMessage)> CheckInMemberAsync(string badgeUid, string readerId);
        Task<(bool Success, Member? Member, string ErrorMessage)> CheckInGroupAsync(string badgeUid, int attendees, string readerId);
    }

    public class LaravelApiService : ILaravelApiService
    {
        private readonly System.Net.Http.HttpClient _httpClient;
        private readonly AppSettings _settings;
        private readonly LoggerService? _logger;

        public LaravelApiService(System.Net.Http.HttpClient httpClient, AppSettings settings, LoggerService? logger = null)
        {
            _httpClient = httpClient;
            _settings = settings;
            _logger = logger;
            _httpClient.Timeout = TimeSpan.FromSeconds(_settings.TimeoutSeconds > 0 ? _settings.TimeoutSeconds : 30);
        }

        public async Task<(bool Success, Member? Member, string ErrorMessage)> CheckInMemberAsync(string badgeUid, string readerId)
        {
            try
            {
                var payload = new 
                { 
                    badge_uid = badgeUid, 
                    reader_id = readerId 
                };



                // Construct URL
                string baseUrl = _settings.ApiBaseUrl.TrimEnd('/');
                string url = $"{baseUrl}/v1/checkin/member";

                _logger?.Info($"API POST Request: {url} | Badge: {badgeUid} | Device: {readerId}");

                // Use HttpRequestMessage to avoid modifying shared HttpClient headers
                var request = new System.Net.Http.HttpRequestMessage(System.Net.Http.HttpMethod.Post, url);
                request.Content = JsonContent.Create(payload);
                
                if (!string.IsNullOrEmpty(_settings.ApiKey))
                {
                    request.Headers.Add("X-API-KEY", _settings.ApiKey);
                    request.Headers.Add("X-DEVICE-ID", _settings.DeviceId);
                }

                var response = await _httpClient.SendAsync(request);
                
                if (response.IsSuccessStatusCode)
                {
                    // Success (200 OK)
                    var apiResponse = await response.Content.ReadFromJsonAsync<ApiResponse<Member>>();
                    
                    if (apiResponse != null && apiResponse.Data != null)
                    {
                        _logger?.Info($"API Success: {apiResponse.Message}");
                        return (true, apiResponse.Data, string.Empty);
                    }
                    _logger?.Warning("API returned 200 OK but invalid data structure.");
                    return (false, null, "Invalid API Response Structure");
                }
                else if (response.StatusCode == System.Net.HttpStatusCode.Forbidden || response.StatusCode == System.Net.HttpStatusCode.Unauthorized)
                {
                    // 403 Forbidden / 401 Unauthorized
                    string msg = $"Access Denied ({(int)response.StatusCode})";
                    Member? deniedMember = null;

                    try 
                    {
                        var jsonString = await response.Content.ReadAsStringAsync();
                        using (var doc = System.Text.Json.JsonDocument.Parse(jsonString))
                        {
                            var root = doc.RootElement;
                            if (root.TryGetProperty("message", out var msgProp))
                            {
                                msg = msgProp.GetString() ?? msg;
                            }
                            
                            if (root.TryGetProperty("data", out var dataProp) && dataProp.ValueKind == System.Text.Json.JsonValueKind.Object)
                            {
                                deniedMember = new Member
                                {
                                    // Manually map fields to avoid casing/serialization issues
                                    FirstName = dataProp.TryGetProperty("firstName", out var fn) ? fn.GetString() ?? "" : "",
                                    LastName = dataProp.TryGetProperty("lastName", out var ln) ? ln.GetString() ?? "" : "",
                                    PhotoUrl = dataProp.TryGetProperty("photoUrl", out var ph) ? ph.GetString() ?? "" : "",
                                    PlanName = dataProp.TryGetProperty("planName", out var pl) ? pl.GetString() ?? "" : "",
                                    ExpiryDate = dataProp.TryGetProperty("expiryDate", out var ed) ? ed.GetString() ?? "" : "",
                                    RemainingSessions = dataProp.TryGetProperty("remainingSessions", out var rs) && rs.ValueKind == System.Text.Json.JsonValueKind.Number ? rs.GetInt32() : (int?)null
                                };
                            }
                        }
                    }
                    catch 
                    {
                        // Fallback
                    }
                    
                    _logger?.Warning($"API Auth Error: {msg}");
                    return (false, deniedMember, msg);
                }
                else if (response.StatusCode == System.Net.HttpStatusCode.NotFound)
                {
                    _logger?.Warning($"API Not Found: Badge {badgeUid} unknown.");
                    return (false, null, "Badge Unknown");
                }
                else
                {
                    _logger?.Error($"API Error {response.StatusCode}: {response.ReasonPhrase}");
                    return (false, null, $"API Error: {response.ReasonPhrase}");
                }
            }
            catch (System.Net.Http.HttpRequestException ex)
            {
                // Network Error - CRITICAL: Throw this so the caller knows we are OFFLINE
                _logger?.Error($"API Network Exception: {ex.Message}");
                throw; 
            }
            catch (System.Exception ex)
            {
                // Other unexpected errors
                _logger?.Error($"API Unexpected Exception: {ex.Message}");
                throw; 
            }
        }
        public async Task<(bool Success, Member? Member, string ErrorMessage)> CheckInGroupAsync(string badgeUid, int attendees, string readerId)
        {
            try
            {
                 var payload = new 
                 { 
                     badge_uid = badgeUid, 
                     attendees = attendees,
                     reader_id = readerId 
                 };

                 string baseUrl = _settings.ApiBaseUrl.TrimEnd('/');
                 string url = $"{baseUrl}/v1/checkin/group";
                 _logger?.Info($"API POST Request: {url} | Badge: {badgeUid} | Count: {attendees}");

                 var request = new System.Net.Http.HttpRequestMessage(System.Net.Http.HttpMethod.Post, url);
                 request.Content = JsonContent.Create(payload);
                 
                 if (!string.IsNullOrEmpty(_settings.ApiKey))
                 {
                     request.Headers.Add("X-API-KEY", _settings.ApiKey);
                     request.Headers.Add("X-DEVICE-ID", _settings.DeviceId);
                 }

                 var response = await _httpClient.SendAsync(request);
                 
                 // Reuse handling logic? 
                 // For now, duplicate standard handling for safety
                 if (response.IsSuccessStatusCode)
                 {
                     var apiResponse = await response.Content.ReadFromJsonAsync<ApiResponse<Member>>();
                     if (apiResponse != null && apiResponse.Data != null)
                     {
                         _logger?.Info($"API Success (Group): {apiResponse.Message}");
                         return (true, apiResponse.Data, string.Empty);
                     }
                     return (false, null, "Invalid API Response");
                 }
                 else
                 {
                     // Return errors
                     string error = await ExtractError(response);
                     return (false, null, error);
                 }
            }
            catch (Exception ex)
            {
                _logger?.Error($"API Group Error: {ex.Message}");
                throw;
            }
        }

        private async Task<string> ExtractError(System.Net.Http.HttpResponseMessage response)
        {
             // 403 Forbidden / 401 Unauthorized
            string msg = $"Access Denied ({(int)response.StatusCode})";

            try 
            {
                var jsonString = await response.Content.ReadAsStringAsync();
                using (var doc = System.Text.Json.JsonDocument.Parse(jsonString))
                {
                    var root = doc.RootElement;
                    if (root.TryGetProperty("message", out var msgProp))
                    {
                        msg = msgProp.GetString() ?? msg;
                    }
                }
            }
            catch 
            {
               // Fallback
            }
            return msg;
        }
    }
}
