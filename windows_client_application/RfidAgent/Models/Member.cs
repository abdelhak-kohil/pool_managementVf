using System;
using System.Text.Json.Serialization;

namespace RfidAgent.Models
{
    public class Member
    {
        [JsonPropertyName("id")]
        public int Id { get; set; }

        [JsonPropertyName("firstName")]
        public string FirstName { get; set; } = string.Empty;

        [JsonPropertyName("lastName")]
        public string LastName { get; set; } = string.Empty;

        [JsonPropertyName("photoUrl")]
        public string PhotoUrl { get; set; } = string.Empty;

        [JsonPropertyName("planName")]
        public string PlanName { get; set; } = string.Empty;

        [JsonPropertyName("isActive")]
        public bool IsActive { get; set; }

        [JsonPropertyName("expiryDate")]
        public string ExpiryDate { get; set; } = string.Empty; // Keep as string for simple display or DateTime
        
        public string FullName => $"{FirstName} {LastName}";
    }
}
