<?php
/**
 * Template Name: Services Injection Moulding 
 */

get_header(); ?>
<script>
// Scroll Animation Logic
    window.addEventListener('scroll', () => {
      const line = document.getElementById('line-progress');
      const rows = document.querySelectorAll('.timeline-row');
      const dots = document.querySelectorAll('.dot');
      const innerDots = document.querySelectorAll('.inner-dot');

      const scrollPos = window.scrollY + (window.innerHeight * 0.5);
      const section = line.closest('section');
      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;

      // Line Progress Calculation
      let progress = ((scrollPos - sectionTop) / sectionHeight) * 100;
      line.style.height = Math.min(Math.max(progress, 0), 100) + '%';

      // Activate Dots & Glowing Core
      rows.forEach((row, i) => {
        const rowTop = row.getBoundingClientRect().top + window.scrollY;
        if (scrollPos > rowTop) {
          // Active Glow State
          dots[i].classList.add('border-[#C8FF00]', 'shadow-[0_0_20px_rgba(200,255,0,0.6)]');
          innerDots[i].classList.replace('bg-white/10', 'bg-[#C8FF00]');
          innerDots[i].classList.add('shadow-[0_0_10px_#C8FF00]');
        } else {
          // Inactive State
          dots[i].classList.remove('border-[#C8FF00]', 'shadow-[0_0_20px_rgba(200,255,0,0.6)]');
          innerDots[i].classList.replace('bg-[#C8FF00]', 'bg-white/10');
          innerDots[i].classList.remove('shadow-[0_0_10px_#C8FF00]');
        }
      });
    });
</script>
<main class="im-page-main">
	<?php
// Featured image URL uthane ke liye
$hero_bg = get_the_post_thumbnail_url(get_the_ID(), 'full');
// Agar image nahi hai toh default image lagane ke liye
if (!$hero_bg) {
    $hero_bg = get_template_directory_uri() . '/assets/images/Services/Injection-Moulding.png';
}
?>

<section class="relative min-h-[265px] w-full flex flex-col items-center bg-center bg-cover"
    style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/Services/Injection Moulding.png'); ">

    <!-- Aapka Navbar (Header) yahan rahega jo aapne diya hai -->
    <!-- Note: Navbar ko 'fixed' ki jagah 'absolute' rakhein agar sirf is section mein chahiye -->
    
    <div class="absolute bottom-10 left-0 w-full px-6 lg:px-20 font-regular font-[Helvetica] text-[20px]">
      <div class="max-w-7xl mx-auto flex items-center gap-2 text-white font-medium">
        <a href="<?php echo home_url(); ?>" class="hover:underline">Home</a>
        <span class="text-white">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M9 5l7 7-7 7" stroke-width="2"></path>
          </svg>
        </span>
        <a href="#" class="hover:underline">Services</a>
        <span class="text-white mx-1">/</span>
        <span class="text-[#C8FF00] hover:underline"><?php the_title(); ?></span>
      </div>
    </div>
</section>
<!--==================== Our Core Service start ============ -->
<section class="py-16 max-w-7xl mx-auto px-6 bg-[#F5F6FA]">
        <div class="reveal flex flex-col lg:flex-row items-start justify-between gap-12">
            
            <div class=" max-w-7xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#D7FF80]/50 rounded-full mb-6">
                    <span class="w-2 h-2 rounded-full bg-[#153F45]"></span>
                    <span class="font-[Poppins] font-medium text-[16px] text-[#19191A] uppercase tracking-wide">Our Core Service</span>
                </div>
                <h1 class="reveal text-[40px] font-[Helvetica] font-medium text-[#19191A] leading-tight mb-6">
                    Precision <br> Injection Moulding Solution
                </h1>
                <p class="reveal text-[#767C8C] text-[16px] font-medium font-[montserrat] leading-relaxed max-w-xl">
                    Delivering high-quality plastic components through advanced injection moulding technology,
                     precision engineering, and reliable production standards to ensure durability, consistency, and exceptional performance for every project.
                </p>
            </div>

            <div class="reveal max-w-7xl grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white p-[10px] rounded-[24px] shadow-sm">
                <div class="p-8 border border-[#EBEDF0] rounded-[12px]">
                    <h3 class="text-[#15181F] text-[32px] font-medium font-[montserrat] mb-1 tracking-wide">±0.01mm</h3>
                    <p class="text-[#474C59] text-[18px] font-regular font-[montserrat] mb-1">Tolerance Accuracy</p>
                </div>
                <div class="p-8 border border-[#EBEDF0] rounded-[12px]">
                    <h3 class="text-[#15181F] text-[32px] font-medium font-[montserrat] mb-1 tracking-wide">30–2000T</h3>
                    <p class="text-[#474C59] text-[18px] font-regular font-[montserrat] mb-1">Clamp Tonnage Range</p>
                </div>
                <div class="p-8 border border-[#EBEDF0] rounded-[12px]">
                    <h3 class="text-[#15181F] text-[32px] font-medium font-[montserrat] mb-1 tracking-wide">100+</h3>
                    <p class="text-[#474C59] text-[18px] font-regular font-[montserrat] mb-1">Material Options</p>
                </div>
                <div class="p-8 border border-[#EBEDF0] rounded-[12px]">
                    <h3 class="text-[#15181F] text-[32px] font-medium font-[montserrat] mb-1 tracking-wide">24hr</h3>
                    <p class="text-[#474C59] text-[18px] font-regular font-[montserrat] mb-1">Quote Turnaround</p>
                </div>
            </div>
            
        </div>
    </section>
<!--==================== Our Core Service start end============ -->
<!--==================== Available Machines Tools ============ -->
<!-- <section class="py-16 max-w-7xl mx-auto px-6 bg-[#F5F6FA]">
        <div class="reveal flex flex-col lg:flex-row items-start justify-between gap-12">
            
            <div class=" max-w-7xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#D7FF80]/50 rounded-full mb-6">
                    <span class="w-2 h-2 rounded-full bg-[#153F45]"></span>
                    <span class="font-[Poppins] font-medium text-[16px] text-[#19191A] uppercase tracking-wide">Our Core Service</span>
                </div>
                <h1 class="reveal text-[40px] font-[Helvetica] font-medium text-[#19191A] leading-tight mb-6">
                    Precision <br> Injection Moulding Solution
                </h1>
                <p class="reveal text-[#767C8C] text-[16px] font-medium font-[montserrat] leading-relaxed max-w-xl">
                    Delivering high-quality plastic components through advanced injection moulding technology,
                     precision engineering, and reliable production standards to ensure durability, consistency, and exceptional performance for every project.
                </p>
            </div>

            <div class="reveal max-w-7xl grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white p-[10px] rounded-[24px] shadow-sm">
                <div class="p-8 border border-[#EBEDF0] rounded-[12px]">
                    <h3 class="text-[#15181F] text-[32px] font-medium font-[montserrat] mb-1 tracking-wide">±0.01mm</h3>
                    <p class="text-[#474C59] text-[18px] font-regular font-[montserrat] mb-1">Tolerance Accuracy</p>
                </div>
                <div class="p-8 border border-[#EBEDF0] rounded-[12px]">
                    <h3 class="text-[#15181F] text-[32px] font-medium font-[montserrat] mb-1 tracking-wide">30–2000T</h3>
                    <p class="text-[#474C59] text-[18px] font-regular font-[montserrat] mb-1">Clamp Tonnage Range</p>
                </div>
                <div class="p-8 border border-[#EBEDF0] rounded-[12px]">
                    <h3 class="text-[#15181F] text-[32px] font-medium font-[montserrat] mb-1 tracking-wide">100+</h3>
                    <p class="text-[#474C59] text-[18px] font-regular font-[montserrat] mb-1">Material Options</p>
                </div>
                <div class="p-8 border border-[#EBEDF0] rounded-[12px]">
                    <h3 class="text-[#15181F] text-[32px] font-medium font-[montserrat] mb-1 tracking-wide">24hr</h3>
                    <p class="text-[#474C59] text-[18px] font-regular font-[montserrat] mb-1">Quote Turnaround</p>
                </div>
            </div>
            
        </div>
    </section> -->
<!--==================== Available Machines Tools end============ -->

  <!-- ================= about service start ================= -->
  <section class="bg-[#142F32] text-white py-16 px-4 sm:px-6 lg:px-12">
    <div class="reveal max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-10 items-center">
      <!-- LEFT CONTENT -->
      <div>
        <!-- Tag box-->
        <div class="reveal flex">
          <div class="relative group">
            <div
              class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]">
            </div>

            <div
              class="flex items-center gap-3 px-6 py-2 bg-[#051616]/50 border border-white/10 rounded-2xl backdrop-blur-sm">
              <span class="w-2 h-2 bg-white rounded-full"></span>

              <span class="text-white text-[12px] lg:text-[18px] font-[Poppins] font-light tracking-wide">
                About The Service
              </span>
            </div>
          </div>
        </div>

        <!-- Heading -->
        <h2
          class="reveal font-[Helvetica] text-[24px] lg:text-[40px] text-[#FFFFFF] font-semibold tracking-5% py-2 leading-tight tracking-widest">
          Professional Injection <br>
          <span class="text-[#C8FF00]/40">Moulding Solutions</span>
        </h2>

        <!-- Description -->
        <p
          class="reveal mt-2 sm:text-base max-w-2xl text-[#FFFFFF] font-regular text-[18px] font-[Montserrat] leading-relaxed py-2">
          Injection moulding is one of the most efficient and reliable manufacturing processes for producing
          high-quality plastic components.At Mould Injection, we provide precision plastic injection moulding services
          designed to meet the needs of modern manufacturing industries. Our advanced machinery and experienced
          engineers ensure consistent production, excellent surface finish, and dimensional accuracy. From product
          prototyping to full-scale manufacturing, our team supports clients throughout the entire production cycle.
        </p>


        <div class="grid grid-cols-1 gap-4 pt-4">
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">Precision engineering with ±0.01mm
              tolerance</span>
          </div>
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">Advanced thermoplastic & thermoset
              processing</span>
          </div>
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">Multi-cavity tooling for high
              efficiency</span>
          </div>
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">Surface finish from SPI A-1 to
              D-3</span>
          </div>
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">Material traceability & quality
              certificates</span>
          </div>
          <div class="reveal flex items-center gap-3">
            <div
              class="relative flex items-center justify-center w-6 h-6 rounded-full border border-white/20 bg-white/10 backdrop-blur-md shadow-inner shadow-white/5 ">

              <div class="absolute inset-0 rounded-full border-t-2 border-l-2 border-white/30 z-0"></div>

              <div
                class="absolute inset-2 bg-gradient-to-br from-transparent via-white/5 to-white/10 rounded-full z-10 blur-[1px]">
              </div>

              <div class="relative z-20 animate-[spin_s_linear_infinite_reverse]">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>

              </div>

            </div>
            <span class="text-white font-[Montserrat] font-regular text-[18px]">ISO 9001 certified production
              partners</span>
          </div>


        </div>
      </div>

      <div class="reveal w-full  flex justify-center">
        <div class="relative group">
          <div class="relative z-10 rounded-[40px] overflow-hidden  shadow-2xl">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Our Capabilities.png" class="w-full h-auto object-cover" alt="Factory Team">

          </div>

          <div class="absolute -inset-4 bg-[#E9FFB2]/5 blur-3xl rounded-full z-0 pointer-events-none"></div>
        </div>
      </div>

    </div>
  </section>
  <!-- ================= about service end ================= -->
  <!-- ================= Moulding Process start================= -->
  <section class="relative py-20 overflow-hidden bg-[#00191C]">
    <div class="reveal text-center mb-32 px-6">
      <p class="text-[#C8FF00] font-medium text-[20px] tracking-[0.3em] font-[Helvetica] mb-4">Step by Step</p>
      <h2 class="font-medium text-[40px] font-[Helvetica] text-[#FFFFFF] mb-2">
        Our Injection <span class="text-[#C8FF00] ">Moulding Process</span>
      </h2>
      <p class="text-[#FFFFFF] max-w-7xl mx-auto text-[#18px] font-[Montserrat] font-regular">
        A proven four-step manufacturing process delivering consistent, high-quality results.
      </p>
    </div>

    <div class="reveal max-w-6xl mx-auto relative px-6">

      <div
        class="hidden lg:block absolute left-1/2 -translate-x-1/2 top-0 h-full w-[14px] bg-glassBg/30 border border-white/5 rounded-full overflow-hidden shadow-[inset_0_0_10px_rgba(0,0,0,0.5)]">
        <div id="line-progress"
          class="w-full h-0 bg-[#C8FF00]/30 rounded-full transition-all duration-500 ease-out relative shadow-[0_0_20px_rgba(200,255,0,0.6)]">
          <div
            class="absolute left-1/2 -translate-x-1/2 w-[2px] h-full bg-[#C8FF00] shadow-[0_0_15px_#C8FF00,0_0_5px_#fff]">
          </div>
        </div>
      </div>

      <div class="space-y-24 lg:space-y-1">

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pr-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->

              <div
                class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Services/icon/Mould Clamp.png" alt="Mould Clamp" class=" shadow-md" />
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Mould Clamp
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                The mould (tool) is placed in the injection moulding machine, and the two halves of the mould are closed
                tightly by the clamping unit. This prepares the mould for injection.
              </p>
            </div>
          </div>

          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="hidden lg:block w-1/2"></div>
        </div>

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="hidden lg:block w-1/2"></div>
          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pl-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15),rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->

              <div
                class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images//Services/icon/Injection.png" alt="Injection" class=" shadow-md" />
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Injection
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                Plastic pellets are melted in the barrel, and the molten plastic is injected into the mould cavity
                through the nozzle and runner system.
              </p>
            </div>
          </div>
        </div>

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pr-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->

              <div
                class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Services/icon/CoolingSolidifying.png" alt="Cooling / Solidifying"
                  class=" shadow-md" />
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Cooling / Solidifying
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                The molten plastic cools and hardens inside the mould. Cooling channels in the mould help reduce the
                temperature so the plastic keeps its shape.
              </p>
            </div>
          </div>
          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="hidden lg:block w-1/2"></div>
        </div>

        <div class="timeline-row relative flex flex-col lg:flex-row items-center group reveal">
          <div class="hidden lg:block w-1/2"></div>
          <div
            class="dot hidden lg:flex absolute left-1/2 -translate-x-1/2 w-11 h-11 rounded-full bg-darkGreen border-2 border-lime/20 items-center justify-center z-10 transition-all duration-500">
            <div class="inner-dot w-3 h-3 rounded-full bg-white/10 transition-all duration-500"></div>
          </div>
          <div class="w-full lg:w-[551px] lg:h-[280px] lg:pl-20 ">
            <div
              class="relative group bg-[#616161]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] transition-all duration-500 group-hover:-translate-y-2 group-hover:border-lime/30 group-hover:bg-glassBg/50">
              <!-- Neon Line -->
              <div
                class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
              </div>
              <!-- Border Glow -->

              <div
                class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Services/icon/EjectionFinishedPart.png" alt="Ejection Finished Part"
                  class=" shadow-md" />
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Ejection / Finished Part
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                The mould opens and ejector pins push the finished plastic part out of the mould. The part is then ready
                for trimming or use.
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
  <!-- ================= Moulding Process end ================= -->
  <!-- ================= faq section start ================= -->
  <section class="w-full bg-[#142F32] py-16 px-6 md:px-12 lg:px-24 font-[Poppins]">

    <div class="reveal max-w-4xl mx-auto text-center pb-4">
      <p class="reveal text-[#C8FF00] font-medium font-[Helvetica] text-[20px] tracking-widest  uppercase">FAQ</p>
      <h2 class="text-white text-[24px] md:text-[40px] font-medium font-[Helvetica] text-[20px] ">
        Frequently Asked <span class="text-[#C8FF00]">Questions</span>
      </h2>
    </div>

    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              01
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What is mould injection?</h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            Mould injection, or injection moulding, is a process where melted plastic is injected into a mould to create parts and products.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              02
            </span>

            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What is the purpose of the mould in injection moulding?</h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            The mould shapes the melted plastic into the required finished part.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              03
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What type of machines do we use?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            We work with hydraulic, electric, and hybrid injection moulding machines in various tonnage sizes.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              04
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What are the basic steps of injection moulding?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            Plastic is melted, injected into the mould, cooled, then the finished part is ejected.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              05
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What is clamp tonnage?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            Clamp tonnage is the force used to keep the mould closed during injection.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              06
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What is a runner in a mould?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            A runner is the channel that guides melted plastic from the nozzle into the mould cavity.
          </p>
        </div>
      </div>

    </div>
    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">

      <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
        <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
          <div class="flex items-center gap-4 md:gap-6">
            <span
              class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
              07
            </span>
            <h3 class="text-white text-[16px] md:text-[26px] font-[Helvetica] leading-tight">
              What is flash in injection moulding?
            </h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            Flash is excess plastic that leaks between mould
             surfaces and forms thin unwanted edges.
          </p>
        </div>
      </div>

    </div>
  </section>

  <!-- ================= FAQ section end ================= -->
  <!-- ================= Join Us Now section end ================= -->

  <section class="w-full bg-[#f4f7f6] px-6 py-20 lg:px-20 lg:py-24">
    <div
      class="reveal max-w-7xl mx-auto rounded-[24px] bg-[#153F45] text-white p-10 flex flex-col items-center justify-center text-center shadow-2xl shadow-[#153F45]/10">

      <div class="relative group">
        <div
          class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]">
        </div>

        <div class="flex items-center gap-3 px-6 py-2  border border-white/10 rounded-[10px] backdrop-blur-sm">
          <span class="w-2 h-2 bg-white rounded-full"></span>

          <span class="text-white font-regular text-[18px] font-[Poppins] font-light tracking-wide">
            Join Us Now
          </span>
        </div>
      </div>

      <h2
        class="text-[32px] md:text-[40px] lg:text-[40px] font-bold font-[Poppins] tracking-wide leading-tight  mt-3">
        Ready to Start
        <span class="text-[#C8FF00]">Your Project?</span>
      </h2>

      <p class="text-[16px] md:text-[16px] text-slate-200 font-[Poppins] leading-relaxed max-w-2xl mb-6 mt-3">
        Get a free quote for your injection moulding project. Our team will match you with the best manufacturer for
        your requirements.
      </p>

      <a href="/contact-us"
        class="inline-flex items-center gap-3 rounded-full border border-white px-6 py-2 text-[12px] md:text-[18px] lg:text-[18px] font-medium font-[Montserrat] text-white transition-all  active:scale-95">
        Request a Quote
        <span class="text-xl leading-none  transition-transform"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right.png" alt=""
            class="w-[20px] h-[20px]"></span>
      </a>

    </div>
  </section>
  <!-- ================= Join Us Now section end ================= -->
</main>

<?php get_footer(); ?>
