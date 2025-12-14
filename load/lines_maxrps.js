import http from "k6/http";
import { check } from "k6";

export const options = {
    scenarios: {
        ramp: {
            executor: "ramping-arrival-rate",
            timeUnit: "1s",
            startRate: 20,
            preAllocatedVUs: 100,
            maxVUs: 1500,
            stages: [
                { target: 50, duration: "20s" },
                { target: 100, duration: "20s" },
                { target: 150, duration: "20s" },
                { target: 200, duration: "20s" },
                { target: 250, duration: "20s" },
                { target: 300, duration: "20s" },
            ],
        },
    },
    thresholds: {
        http_req_failed: ["rate<0.01"],
        http_req_duration: ["p(95)<500"],
    },
};

const BASE_URL = __ENV.BASE_URL || "https://odds.lc";
const SPORT = __ENV.SPORT || "soccer_uefa_europa_conference_league";

export default function () {
    const res = http.get(`${BASE_URL}/api/lines?sport=${encodeURIComponent(SPORT)}`, {
        tags: { name: "lines" },
    });

    check(res, { "200": (r) => r.status === 200 });
}
