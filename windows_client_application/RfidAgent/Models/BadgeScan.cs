using System;

namespace RfidAgent.Models
{
    public class BadgeScan
    {
        public int Id { get; set; }
        public string Uid { get; set; } = string.Empty;
        public DateTime Timestamp { get; set; }
        public bool IsSynced { get; set; }
        public string ReaderId { get; set; } = "PC_1"; // Default, could be configurable
    }
}
