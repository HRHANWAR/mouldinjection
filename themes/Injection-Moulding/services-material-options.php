<?php
/**
 * Template Name: Service - Material Options
 * Template Post Type: page
 */
get_header();
?>

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

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">

        <!-- Left Content -->
        <div>
          <!-- Badge -->
          <span
            class="reveal inline-flex items-center gap-2 bg-[#D7FF80]/50 text-[#19191A] text-[16px] font-[Poppins] font-medium px-4 py-1 rounded-full mb-6 tracking-widest">
            <span class="w-2 h-2 bg-[#153F45] rounded-full"></span>
            Material Options
          </span>

          <!-- Heading -->
          <h2
            class="reveal text-3xl md:text-[40px] text-[#19191A]  font-[Helvetica] font-medium leading-tight mb-4 tracking-wide">
            High-Quality <br class="hidden sm:block" />
            Thermoplastic Materials
          </h2>

          <!-- Description -->
          <p class="text-[#767C8C] text-[16px] font-[Montserrat] font-medium lg:max-w-2xl">
            We offer a wide range of high-quality thermoplastic materials suitable for different applications,
            industries, and product requirements. Selecting the right material ensures optimal strength, durability,
            appearance, and cost efficiency.
          </p>
        </div>

        <!-- Right Cards -->
        <div class="reveal grid grid-cols-1 sm:grid-cols-2 gap-4">

          <!-- Card 1 -->
          <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex gap-3 items-center">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Material option/material option.png" class="w-[28px] h-[28px] " alt="Icon">
              <div class="text-[24px] font-[Helvetica] font-semibold tracking-widest text-[#15181F]">10+</div>
            </div>
            <div class="text-[18px] font-[Montserrat] font-regular  text-[#474C59]">Material Types</div>
          </div>

          <!-- Card 2 -->
          <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex gap-3 items-center">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Material option/Quality Assured.png" class="w-[28px] h-[28px] " alt="Icon">
              <div class="text-[24px] font-[Helvetica] font-semibold tracking-widest text-[#15181F]">100%</div>
            </div>
            <div class="text-[18px] font-[Montserrat] font-regular  text-[#474C59]">Quality Assured</div>
          </div>

          <!-- Card 3 -->
          <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex gap-3 items-center">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Material option/Frame.png" class="w-[28px] h-[28px] " alt="Icon">
              <div class="text-[24px] font-[Helvetica] font-semibold tracking-widest text-[#15181F]">Full</div>
            </div>
            <div class="text-[18px] font-[Montserrat] font-regular  text-[#474C59]">Traceability</div>
          </div>

          <!-- Card 4 -->
          <div class="bg-white rounded-xl p-6 shadow-sm border">
            <div class="flex gap-3 items-center">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images//Material option/Grade Sourcing.png" class="w-[28px] h-[28px] " alt="Icon">
              <div class="text-[24px] font-[Helvetica] font-semibold tracking-widest text-[#15181F]">Custom</div>
            </div>
            <div class="text-[18px] font-[Montserrat] font-regular  text-[#474C59]">Grade Sourcing</div>
          </div>
        </div>

      </div>

    </div>
  </section>
                                                            <!-- ================= Material option end ================= -->
                                                            <!--  ================= Common Materials start =================  -->
  <section class="py-24 bg-[#00191C] text-white font-sans overflow-hidden">
    <div class="max-w-7xl mx-auto px-6">

      <div class="reveal text-center mb-16">
        <span class="text-[#C8FF00] text-[20px] font-medium font-[Helvetica] tracking-wide block mb-4">Common
          Materials</span>
        <h2 class="text-3xl md:text-[40px] font-medium font-[Helvetica] leading-tight tracking-wider">
          Materials <span class="text-[#C8FF00]">We Use</span>
        </h2>
        <p class="text-[#FFFFFF] text-[18px] font-regular font-[Montserrat] mt-4 max-w-4xl mx-auto ">
          Our team works with trusted suppliers to source reliable materials that deliver consistent performance during
          the injection moulding process.
        </p>
      </div>
      <div class="reveal grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-20">
        <!-- CARD -->

        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Polypropylene (PP)
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            One of the most widely used plastics in injection moulding. Offers excellent chemical resistance,
            flexibility, and durability while remaining lightweight and cost-effective.
          </p>
          <h3 class="text-[#C8FF00] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Applications
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            Packaging, containers, automotive components, consumer products
          </p>
          <div
            class="grid grid-cols-3 text-[#C8FF00] font-[400] text-[16px] font-[Montserrat] text-medium leading-relaxed tracking-wide items-center gap-2 py-4">

            <div
              class="font-medium bg-[#3B524C]/50  text-[16px] font-[Montserrat] leading-relaxed  tracking-wide text-center rounded-full px-2">
              RP398T
            </div>
            <div
              class="font-medium bg-[#3B524C]/50  text-[16px] font-[Montserrat] leading-relaxed  tracking-wide text-center rounded-full px-2">
              BH374MO
            </div>
            <div
              class="font-medium bg-[#3B524C]/50  text-[16px] font-[Montserrat] leading-relaxed  tracking-wide text-center rounded-full px-2">
              PPR12236
            </div>
            <div
              class="font-medium bg-[#3B524C]/50  text-[16px] font-[Montserrat] leading-relaxed  tracking-wide text-center rounded-full px-2">
              PPC11812
            </div>
          </div>
        </div>
        <!-- CARD -->
        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Acrylonitrile Butadiene Styrene (ABS)
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            A strong and impact-resistant plastic widely used for electronic housings, automotive parts, and durable
            consumer products. Provides excellent strength and a high-quality surface finish.
          </p>
          <h3 class="text-[#C8FF00] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Applications
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            Electronic housings, automotive parts, consumer products
          </p>

        </div>
        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Polyethylene (PE)
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            A tough and flexible plastic with good moisture and chemical resistance. Commonly used for containers,
            packaging, and industrial components.
          </p>
          <h3 class="text-[#C8FF00] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Applications
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            Containers, packaging, industrial parts
          </p>

        </div>
        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Polycarbonate (PC)
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            A transparent and highly durable plastic with exceptional impact resistance and heat resistance. Used in
            safety equipment, lenses, and high-performance components.
          </p>
          <h3 class="text-[#C8FF00] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Applications
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            Safety equipment, lenses, high-performance parts
          </p>

        </div>
        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Nylon (PA)
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            A strong engineering plastic known for excellent wear resistance and mechanical strength. Widely used for
            gears, mechanical parts, and industrial applications.
          </p>
          <h3 class="text-[#C8FF00] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Applications
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            Gears, mechanical parts, industrial applications
          </p>

        </div>
        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Polystyrene (PS)
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            A rigid and lightweight plastic commonly used in packaging, disposable products, and consumer goods.
            Provides good moulding performance and surface finish.
          </p>
          <h3 class="text-[#C8FF00] font-semibold text-[20px] font-[Poppins] leading-relaxed py-2 tracking-wide">
            Applications
          </h3>
          <p
            class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed tracking-wide">
            Packaging, disposable products, consumer goods
          </p>

        </div>

      </div>

      <div class="reveal text-center mb-16">
        <span class="text-[#C8FF00] text-[20px] font-medium font-[Helvetica] tracking-wide block mb-4">Polypropylene
          Grades</span>
        <h2 class="text-3xl md:text-[40px] font-medium font-[Helvetica] leading-tight tracking-wider">
          Specialist <span class="text-[#C8FF00]">PP Grades</span>
        </h2>
        <p class="text-[#FFFFFF] text-[18px] font-regular font-[Montserrat] mt-4 max-w-4xl mx-auto ">
          Detailed breakdown of our commonly used polypropylene grades and their properties.
        </p>
      </div>
      <div class="reveal grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- CARD -->

        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2">
            RP398T
          </h3>
          <p class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
            Injection moulding grade with good flow characteristics and reliable processing performance. Ideal for
            durable plastic components.
          </p>
        </div>
        <!-- CARD -->

        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2">
            BH374MO
          </h3>
          <p class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
            Copolymer designed for improved impact resistance and strength. Performs well in products exposed to
            repeated use and mechanical stress.
          </p>
        </div>
        <!-- CARD -->

        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2">
            PPR12236
          </h3>
          <p class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
            A polypropylene random copolymer offering good clarity, toughness, and stability during the moulding
            process.
          </p>
        </div>
        <!-- CARD -->

        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2">
            PPC11812
          </h3>
          <p class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
            A polypropylene copolymer known for its balanced rigidity and impact resistance. Often used for durable
            plastic components.
          </p>
        </div>
        <!-- CARD -->

        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2">
            HP500N
          </h3>
          <p class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
            A polypropylene homopolymer commonly used in injection moulding applications requiring high stiffness and
            good processability.
          </p>
        </div>
        <!-- CARD -->

        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2">
            HJ120UB
          </h3>
          <p class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
            A polypropylene grade offering high flow and consistent moulding performance, often used for thin-wall
            injection moulded parts.
          </p>
        </div>
        <!-- CARD -->

        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2">
            Moplen EP240H
          </h3>
          <p class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
            A polypropylene copolymer designed for products requiring strong impact resistance and durability.
          </p>
        </div>
        <!-- CARD -->

        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2">
            Borealis HE125MO
          </h3>
          <p class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
            A polypropylene material widely used in injection moulding for its excellent mechanical properties and
            process stability.
          </p>
        </div>
        <!-- CARD -->

        <div
          class="relative bg-[#062f2f]/10 rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
          <!-- Neon Line -->
          <div
            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
          </div>
          <!-- Border Glow -->


          <h3 class="text-[#EBEDF0] font-semibold text-[24px] font-[Poppins] leading-relaxed py-2">
            LL6201XR
          </h3>
          <p class="text-[#FFFFFF]-60% font-[400] text-[18px] font-[Montserrat] text-medium leading-relaxed">
            A polypropylene material widely used in injection moulding for its excellent mechanical properties and
            process stability.
          </p>
        </div>
      </div>
  </section>
                                                            <!--  ================= Common Materials end =================  -->

                                                            <!-- ================= Masterbatch Solutions start ================= -->
  <section class="py-24 bg-[#F5F6FA] font-sans">
    <div class="max-w-7xl mx-auto px-6">

      <div class="reveal text-center mb-16">
        <div class="reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-[#D7FF80]/50 mb-6">
          <span class="w-2 h-2 rounded-full bg-[#153F45]"></span>
          <span class="text-[16px] font-medium font-[Poppins] text-[#1A3D2B] tracking-widest">Masterbatch</span>
        </div>
        <h2 class="reveal text-4xl lg:text-[40px] font-medium font-[Helvetica] text-[#19191A] tracking-wide mb-6 ">
          Masterbatch
          Solutions</h2>
        <p class="reveal text-[16px] font-medium font-[Montserrat] text-[#767C8C] max-w-6xl mx-auto leading-relaxed ">
          Masterbatch is a concentrated mixture of pigments and additives used to colour or enhance the properties of
          plastic materials during the injection moulding process. It is supplied in pellet form and blended with the
          base polymer before production, allowing precise control over colour, appearance, and functional properties
          while maintaining consistent processing performance.
        </p>
      </div>

      <div class="reveal bg-white border border-slate-100 rounded-2xl p-8 text-center mb-12 shadow-sm">
        <h3 class="reveal text-[24px] font-bold font-[Montserrat] text-[#0E0E0E] mb-4">Consistent Material Performance
        </h3>
        <p class="text-[18px] font-medium font-[Montserrat] text-[#767C8C] max-w-5xl mx-auto leading-relaxed">
          By combining high-quality base polymers with carefully selected masterbatches, we are able to produce plastic
          components with <span class="text-[18px] font-semibold font-[Montserrat] text-[#474C59]">consistent colour,
            improved durability, and enhanced
            performance</span>.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">

        <div class="reveal bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm">
          <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center mb-2">
            <img src="https://img.icons8.com/ios-filled/50/1e293b/paint-palette.png" class="w-6 h-6" alt="Icon">
          </div>
          <h4 class="reveal text-[24px] font-semibold font-[Helvetica] text-[#15181F] tracking-wide mb-2">Colour
            Masterbatch
          </h4>
          <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59] mb-4 leading-relaxed">
            Colour masterbatch is used to produce consistent and vibrant colours in plastic components. It allows
            manufacturers to achieve accurate colour matching while maintaining uniformity across production batches.
          </p>
          <ul class="space-y-4">
            <li class="reveal flex items-start gap-3">
              <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">New product launches</span>
            </li>
            <li class="reveal flex items-start gap-3">
              <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">Improved surface appearance</span>
            </li>
            <li class="reveal flex items-start gap-3">
              <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">Easy colour customisation</span>
            </li>
            <li class="reveal flex items-start gap-3">
              <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">Reliable repeatability during
                production</span>
            </li>
          </ul>
        </div>

        <div class="reveal bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm">
          <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center mb-2">
            <img src="https://img.icons8.com/ios-filled/50/1e293b/test-tube.png" class="w-6 h-6" alt="Icon">
          </div>
          <h4 class="reveal text-[24px] font-semibold font-[Helvetica] text-[#15181F] tracking-wide mb-2">Additive
            Masterbatch
          </h4>
          <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59] mb-4 leading-relaxed">
            Additive masterbatch is used to enhance the physical or chemical properties of plastic materials. These
            additives are mixed into the base polymer during production.
          </p>
          <ul class="space-y-4">
            <li class="reveal flex items-start gap-3">
              <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">UV stabilisers – Protect plastic
                products from sunlight degradation</span>
            </li>
            <li class="reveal flex items-start gap-3">
              <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">Anti-static additives – Reduce
                static build-up on plastic surfaces</span>
            </li>
            <li class="reveal flex items-start gap-3">
              <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">Flame retardants – Improve fire
                resistance in certain applications</span>
            </li>
            <li class="reveal flex items-start gap-3">
              <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">Slip and anti-block additives –
                Improve surface performance and handling</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="reveal bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm">
        <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center mb-2">
          <img src="https://img.icons8.com/ios-filled/50/1e293b/lightning-bolt.png" class="w-6 h-6" alt="Icon">
        </div>
        <h4 class="reveal text-[24px] font-semibold font-[Helvetica] text-[#15181F] tracking-wide mb-2">Anti-Static
          Masterbatch
        </h4>
        <p class="reveal text-[18px] font-regular font-[Montserrat] text-[#474C59] mb-4 leading-relaxed">
          Anti-static masterbatch is designed to reduce static electricity build-up on plastic surfaces, improving
          handling, cleanliness, and safety in sensitive applications.
        </p>
        <ul class="space-y-4">
          <li class="reveal flex items-start gap-3">
            <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">Reduced static build-up during
              production and use</span>
          </li>
          <li class="reveal flex items-start gap-3">
            <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">Improved product cleanliness –
              attracts less dust and particles</span>
          </li>
          <li class="reveal flex items-start gap-3">
            <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">Safer handling in sensitive or
              electronic environments</span>
          </li>
          <li class="reveal flex items-start gap-3">
            <div class="mt-1 w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="text-[18px] font-regular font-[Montserrat] text-[#474C59]">Consistent static control throughout
              the product</span>
          </li>
        </ul>
      </div>
    </div>
    </div>
  </section>
                                                            <!-- ================= Masterbatch Solutions end ================= -->
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
              How do I choose the right material?</h3>
          </div>

          <div class="flex-shrink-0 ml-4">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
              class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
              style="transform: rotate(0deg);">
          </div>
        </button>

        <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
          <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
            The right material depends on strength, flexibility, appearance, temperature resistance, and end use.
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
              Can you source custom material grades?
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
            Yes, custom and specialist material grades can be sourced based on project requirements.
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
              Do you provide material certificates?
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
            Yes, material certificates can be supplied on request for approved grades and batches.
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
              What is the difference between PP and ABS?
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
            PP is flexible, lightweight, and chemical resistant, while ABS is tougher, stronger, and offers a better surface finish.
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
              Can I use recycled materials?
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
            Yes, recycled and reprocessed materials can be used depending on the product application.
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
              What is masterbatch and why is it used?
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
            Masterbatch is a concentrated additive or colour pellet used to colour plastic or improve material properties.
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
        Ready to Select Your
        <span class="text-[#C8FF00]">Material?</span>
      </h2>

      <p class="text-[16px] md:text-[16px] text-slate-200 font-[Poppins] font-medium max-w-3xl mb-6 mt-3">
        Get expert advice on the best material for your injection moulding project. Our team will help you make the
        right choice.
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



