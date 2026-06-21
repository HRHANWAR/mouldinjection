<?php
/**
 * Template Name: Service - Quality Controle
 * Template Post Type: page
 */
get_header();
?>
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
    style="background-image: url('<?php echo esc_url( get_template_directory_uri() . '/assets/images/SmallMediumBatchRuns/header.png' ); ?>');"

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

<!-- =================Material options start====================== -->
  <section class="w-full bg-[#F5F6FA] py-20">
    <div class="reveal max-w-7xl mx-auto px-4">

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

        <!-- Left Content -->
        <div>
          <!-- Badge -->
          <span
            class="reveal inline-flex items-center gap-2 bg-[#D7FF80]/50 text-[#19191A] text-[16px] font-[Poppins] font-medium px-4 py-1 rounded-full mb-6 tracking-widest">
            <span class="w-2 h-2 bg-[#153F45] rounded-full"></span>
            Quality Controle
          </span>

          <!-- Heading -->
          <h2
            class="reveal text-3xl md:text-[40px] text-[#19191A]  font-[Helvetica] font-medium leading-tight mb-4 tracking-wide">
            Precision Quality <br class="hidden sm:block" /> at Every Stage
          </h2>

          <!-- Description -->
          <p class="text-[#767C8C] text-[16px] font-[Montserrat] font-medium lg:max-w-2xl">
            Quality is at the core of our manufacturing process. We follow strict quality control procedures throughout
            every stage of production to ensure that each component meets the required specifications and industry
            standards.From material selection to final inspection, our team carefully monitors every step of production
            to maintain <span class="font-semibold text-[#474C59]">precision, consistency, and product
              reliability.</span>
          </p>
        </div>

        <!-- Right Cards -->
        <div class="reveal grid grid-cols-1 sm:grid-cols-2 gap-4">


          <div class="pr-8 border-r-2">
            <h3 class="reveal text-[32px] font-medium font-[Helvetica] text-[#15181F] mb-1 tracking-wide">100%</h3>
            <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59]">Batch Inspected</p>
          </div>

          <div class="pl-8">
            <h3 class="reveal text-[32px] font-medium font-[Helvetica] text-[#15181F] mb-1 tracking-wide">ISO 9001</h3>
            <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59] ">Standards Aligned</p>
          </div>

          <div class="col-span-2 h-[1px] bg-slate-200 my-2"></div>

          <div class="pr-8  border-r-2">
            <h3 class="reveal text-[32px] font-medium font-[Helvetica] text-[#15181F] mb-1 tracking-wide">Mitutoyo</h3>
            <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59]">Precision Equipment</p>
          </div>

          <div class="pl-8">
            <h3 class="reveal text-[32px] font-medium font-[Helvetica] text-[#15181F] mb-1 tracking-wide">4-Stage</h3>
            <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59]">QA Process</p>
          </div>
        </div>

      </div>

    </div>
  </section>
  <!-- ================= Material option end ================= -->

  <!-- ================= Moulding Process start================= -->
  <section class="relative py-20 overflow-hidden bg-[#00191C]">
    <div class="reveal text-center mb-32 px-6">
      <p class="text-[#C8FF00] font-medium text-[20px] tracking-wide font-[Helvetica] mb-4">Our Process</p>
      <h2 class="font-medium text-[40px] font-[Helvetica] text-[#FFFFFF] mb-2">
        Quality Assurance <span class="text-[#C8FF00] ">Process</span>
      </h2>
      <p class="text-[#FFFFFF] max-w-7xl mx-auto text-[#18px] font-[Montserrat] font-regular">
        Four key stages ensure every part meets exacting standards.
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


              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Frame 2147230828.png" alt="Mould Clamp" class=" shadow-md" />
                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 1</p>
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Material Verification
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                All raw materials are sourced from trusted suppliers and verified before production begins. This ensures
                the materials meet the required standards for strength, durability, and performance.
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

              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Services/icon/EjectionFinishedPart.png" alt="Ejection Finished Part"
                    class=" shadow-md" />
                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 2</p>
              </div>
              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Process Monitoring
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                During injection moulding, machine parameters such as temperature, pressure, and cycle times are
                carefully monitored. Maintaining stable processing conditions ensures consistent quality across all
                production runs.
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
              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images//Services/icon/Injection.png" alt="Injection" class=" shadow-md" />
                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 3</p>
              </div>

              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Dimensional Inspection
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                We perform regular dimensional inspections during production to confirm that parts meet the specified
                tolerances. This helps ensure that every component fits and functions correctly in its intended
                application.
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
              <div class="flex items-center justify-between">
                <div
                  class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(200,225,100,0.6)] ">
                  <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Services/icon/CoolingSolidifying.png" alt="Cooling / Solidifying"
                    class=" shadow-md" />

                </div>
                <p class="text-transparent font-bold text-[26px] font-[Poppins] tracking-wide"
                  style="-webkit-text-stroke: 2px #A8AEBF; opacity: 0.5;">Step 4</p>
              </div>



              <h3 class="text-[#EBEDF0] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2">
                Visual Inspection
              </h3>
              <p class="text-[#FFFFFF] font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
                Each batch undergoes visual inspection to identify surface defects, moulding inconsistencies, or
                imperfections. This helps maintain the appearance and performance of every part we manufacture.
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
  <section class="py-24 bg-[#F5F6FA] font-sans">
    <div class="reveal max-w-7xl mx-auto px-6">

      <div class="reveal flex flex-col lg:flex-row gap-12 items-start mb-24">

        <div class="reveal w-full lg:w-3/5">
          <div class="reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#D7FF80]/50 mb-4">
            <span class="w-2 h-2 rounded-full bg-[#153F45]"></span>
            <span class="reveal text-[16PX] font-medium text-[#19191A] font-[Poppins] tracking-widest">Precision
              Equipment</span>
          </div>

          <h2 class="reveal text-4xl md:text-[40PX] font-medium text-[#19191A] font-[Helvetica] tracking-widest mb-6">Precision
            Measurement</h2>

          <p class="reveal text-[16PX] font-medium text-[#767C8C] font-[Montserrat] max-w-xl leading-relaxed mb-4">
            To maintain accurate quality control, we use professional measuring instruments from Mitutoyo, a globally
            trusted manufacturer of precision measurement equipment.Mitutoyo instruments allow us to perform highly
            accurate measurements of component dimensions and tolerances during production and final inspection.
          </p>

          <h4 class="reveal text-[20PX] font-semibold text-[#19191A] font-[Montserrat] mb-6">Our equipment includes tools such
            as:</h4>

          <ul class="reveal space-y-4">
            <li class="flex items-center gap-3">
              <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Digital calipers</span>
            </li>
            <li class="reveal flex items-center gap-3">
              <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Micrometers</span>
            </li>
            <li class="reveal flex items-center gap-3">
              <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Height gauges</span>
            </li>
            <li class="reveal flex items-center gap-3">
              <div class="reveal w-6 h-6 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="reveal text-[18PX] font-regular text-[#474C59] font-[Montserrat]">Precision measurement tools</span>
            </li>
          </ul>
        </div>

        <div class="reveal w-full max-w-xl bg-[#142F32] rounded-[32px] p-6 shadow-2xl relative overflow-hidden">
          <div class="relative z-10">
            <div class="flex items-center gap-4 mb-4 ">
              <div
                class="w-10 h-10 bg-[#C8FF00]/10 rounded-lg flex items-center justify-center border border-[#C8FF00]/20">
                <div class=" animate-pulse"> <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Precision Measurement.png" alt=""></div>
              </div>
              <h3 class="reveal text-[22px] font-semibold text-[#EBEDF0] font-[Poppins]">Precision Measurement</h3>
            </div>
            <p class="reveal text-[18px] font-medium text-[#C8CCD9] font-[Montserrat] mb-4">Using Mitutoyo measuring equipment
              to
              ensure accurate dimensions and consistent product quality.</p>
            <div class="space-y-6">
              <div class="border-t border-[#474C59] ">
                <h4 class="text-[22px] font-semibold text-[#EBEDF0] font-[Poppins] tracking-wider mb-3 mt-4">Consistent
                  Results</h4>
                <p class="reveal text-[18px] font-medium text-[#C8CCD9] font-[Montserrat] leading-relaxed ">
                  By combining experienced technicians, modern injection moulding equipment, and precision measuring
                  tools, we are able to maintain <span class="text-white font-bold">high levels of accuracy and
                    consistency in every batch we produce.</span>
                </p>
              </div>
              <div class="pt-6 border-t border-[#474C59] ">
                <h4 class=" text-[22PX] font-semibold text-[#EBEDF0] font-[Poppins]  mb-3">Mitutoyo</h4>
                <p class="reveal text-[18px] font-medium text-[#C8CCD9] font-[Montserrat] leading-relaxed ">
                  A globally trusted manufacturer of precision measurement tools such as calipers, micrometers, height
                  gauges, and coordinate measuring machines (CMM). Known for high accuracy and reliability in industrial
                  inspection.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="reveal text-center mb-16">
        <div class="reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#D7FF80]/50 mb-4">
          <span class="w-2 h-2 rounded-full bg-[#153F45]"></span>
          <span class="reveal text-[16px] font-medium text-[#19191A] font-[Poppins] tracking-widest">Industry Leader</span>
        </div>
        <h3 class="reveal text-[40px] font-medium text-[#19191A] font-[Helvetica] tracking-widest">Common Measurement Tools
          Used in Quality Control
        </h3>
        <p class="reveal text-[16px] font-medium text-[#767C8C] font-[Montserrat] leading-relaxed mb-4">
          We work with equipment from globally recognised brands trusted across the manufacturing industry.
        </p>
      </div>

      <div class="reveal grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-2xl border-l-4 border-[#142F32] shadow-sm hover:shadow-md transition-shadow">
          <h4 class="reveal text-xl font-bold text-slate-900 mb-2">Hexagon Manufacturing Intelligence</h4>
          <p class="reveal text-[18px] font-regular text-[#474C59] font-[Montserrat]  leading-relaxed">
            A leading provider of advanced metrology systems including coordinate measuring machines (CMM), laser
            scanners, and 3D inspection technology used in high-precision manufacturing.
          </p>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-[#142F32] shadow-sm hover:shadow-md transition-shadow">
          <h4 class="reveal text-xl font-bold text-slate-900 mb-2">Renishaw</h4>
          <p class="reveal text-[18px] font-regular text-[#474C59] font-[Montserrat]  leading-relaxed">
            Specialises in high-precision measurement systems, probes, and inspection equipment used in advanced
            manufacturing and quality control environments.
          </p>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-[#142F32] shadow-sm hover:shadow-md transition-shadow">
          <h4 class="reveal text-xl font-bold text-slate-900 mb-2">Keyence</h4>
          <p class="reveal text-[18px] font-regular text-[#474C59] font-[Montserrat]  leading-relaxed">
            Produces high-precision measurement systems including optical measurement devices, laser scanners, and
            automated inspection equipment used in modern production lines.
          </p>
        </div>
        <div class="bg-white p-6 rounded-2xl border-l-4 border-[#142F32] shadow-sm hover:shadow-md transition-shadow">
          <h4 class="reveal text-xl font-bold text-slate-900 mb-2">TESA Technology</h4>
          <p class="reveal text-[18px] font-regular text-[#474C59] font-[Montserrat]  leading-relaxed">
            Known for producing high-quality precision instruments such as calipers, micrometers, and dial indicators
            used for dimensional inspection.
          </p>
        </div>
      </div>
      <div class="reveal max-w-6xl mx-auto px-6 py-12 ">
        <div
          class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row items-center gap-6 group hover:shadow-md transition-shadow duration-300">

          <div
            class="reveal w-14 h-14 bg-[#F0F1F5] rounded-2xl flex items-center justify-center flex-shrink-0 border border-slate-100 group-hover:bg-slate-100 transition-colors">
           <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Group 2085663339.png" alt="">
          </div>

          <p class="reveal text-[18px] font-medium text-[#474C59] font-[Montserrat] leading-relaxed text-center ">
           Our commitment to quality ensures that every product delivered meets strict manufacturing standards.
          </p>

        </div>
      </div>
    </div>
  </section>

  <!-- ================= faq section start ================= -->
  <section class="w-full bg-[#142F32] py-16 px-6 md:px-12 lg:px-24 font-[Poppins]">

    <div class="reveal max-w-4xl mx-auto text-center pb-4">
      <p class="reveal text-[#C8FF00] font-medium font-[Helvetica] text-[20px] tracking-widest uppercase">FAQ
      </p>
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
              What quality standards do you follow?</h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            We work with manufacturers that follow recognised quality systems such as ISO 9001 and industry-specific standards.
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
              What measurement equipment do you use?
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
            Common equipment includes calipers, micrometers, gauges, CMM machines, and visual inspection tools.
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
              Do you inspect every batch?
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
            Yes, batch inspections and quality checks are carried out based on project requirements.
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
              Can you provide inspection reports?
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
            Yes, inspection reports and quality documentation can be provided on request.
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
        Need Quality-Assured
        <span class="text-[#C8FF00]">Components?</span>
      </h2>

      <p class="text-[16px] md:text-[16px] text-slate-200 font-[Poppins] font-medium max-w-3xl mb-6 mt-3">
        Get in touch to discuss your quality requirements and how we can deliver precision-manufactured parts.
      </p>

      <a href="/contact-us"
        class="inline-flex items-center gap-3 rounded-full border border-white px-6 py-2 text-[12px] md:text-[18px] lg:text-[18px] font-medium font-[Montserrat] text-white transition-all  active:scale-95">
        Request Material Advice
        <span class="text-xl leading-none  transition-transform"><img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right.png" alt=""
            class="w-[20px] h-[20px]"></span>
      </a>

    </div>
  </section>
  <!-- ================= Join Us Now section end ================= -->













</main>









<?php get_footer(); ?>



