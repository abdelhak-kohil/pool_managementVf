using System;

namespace RfidAgent.Models
{
    public class OfflineScan
    {
        public int Id { get; set; }
        public string BadgeUid { get; set; } = "";
        public string ReaderId { get; set; } = "";
        public DateTime Timestamp { get; set; }
        public bool IsSynced { get; set; } = false;
    }
}
